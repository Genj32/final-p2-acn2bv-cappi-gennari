<?php

require_once 'database.php';
header('Content-Type: application/json; charset=utf-8');

// Inicialización
$pdo = conectar_db();
$metodo = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);


// GET - Listar y buscar guerreros 

if ($metodo === 'GET') {
    $buscar = trim($_GET['buscar'] ?? '');
    $raza = $_GET['raza'] ?? '';
    $pagina = max(1, intval($_GET['pagina'] ?? 1));
    $porPagina = 8;
    $offset = ($pagina - 1) * $porPagina;

    $where = [];
    $params = [];

    // Búsqueda por nombre
    if ($buscar !== '') {
        $where[] = "nombre LIKE :buscar";
        $params[':buscar'] = "%$buscar%";
    }

    // Filtro por raza
    if ($raza !== '') {
        $where[] = "raza = :raza";
        $params[':raza'] = $raza;
    }

    $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

    // Contar total de registros
    $stmtCount = $pdo->prepare("SELECT COUNT(*) as total FROM guerreros $whereClause");
    $stmtCount->execute($params);
    $total = $stmtCount->fetch()['total'];

    // Obtener guerreros paginados
    $stmt = $pdo->prepare("
        SELECT id, nombre, raza, descripcion, imagen, poder
        FROM guerreros 
        $whereClause
        ORDER BY id DESC
        LIMIT :limit OFFSET :offset
    ");

    // Vincular parámetros de búsqueda/filtro
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    // Vincular parámetros de paginación
    $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $guerreros = $stmt->fetchAll();

    // Obtener razas únicas para el filtro
    $stmtRazas = $pdo->query("SELECT DISTINCT raza FROM guerreros ORDER BY raza");
    $razas = $stmtRazas->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'data' => $guerreros,
        'razas' => $razas,
        'paginacion' => [
            'actual' => $pagina,
            'total' => ceil($total / $porPagina),
            'totalRegistros' => $total
        ]
    ]);
    exit;
}


// POST - Crear nuevo guerrero

if ($metodo === 'POST') {
    // Validación de campos obligatorios
    if (!$input || empty(trim($input['nombre'] ?? '')) || empty(trim($input['raza'] ?? '')) || empty(trim($input['descripcion'] ?? ''))) {
        echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios (nombre, raza, descripción)']);
        exit;
    }

    // consulta SQL 
    $sql = "INSERT INTO guerreros (nombre, raza, descripcion, imagen, poder) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    // Array de valores para la ejecución
    $valores = [
        trim($input['nombre']),
        trim($input['raza']),
        trim($input['descripcion']),
        trim($input['imagen'] ?? ''),
        trim($input['poder'] ?? 'Desconocido')
    ];

    try {
        $stmt->execute($valores);
        $lastId = $pdo->lastInsertId();

        // Preparar respuesta con los datos del nuevo guerrero
        $nuevo = [
            'id' => $lastId,
            'nombre' => $valores[0],
            'raza' => $valores[1],
            'descripcion' => $valores[2],
            'imagen' => $valores[3],
            'poder' => $valores[4]
        ];

        echo json_encode(['success' => true, 'id' => $lastId, 'data' => $nuevo]);
        exit;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Error al guardar en la base de datos.']);
        exit;
    }
}

// PUT - Actualizar guerrero existente

if ($metodo === 'PUT') {
    $id = $_GET['id'] ?? null;

    // Validación de ID
    if (!filter_var($id, FILTER_VALIDATE_INT)) {
        echo json_encode(['success' => false, 'error' => 'ID de actualización inválido.']);
        exit;
    }

    // Validación de campos obligatorios
    if (!$input || empty(trim($input['nombre'] ?? '')) || empty(trim($input['raza'] ?? '')) || empty(trim($input['descripcion'] ?? ''))) {
        echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios (nombre, raza, descripción) para actualizar']);
        exit;
    }

    $sql = "UPDATE guerreros SET nombre=?, raza=?, descripcion=?, imagen=?, poder=? WHERE id=?";
    $stmt = $pdo->prepare($sql);

    $valores = [
        trim($input['nombre']),
        trim($input['raza']),
        trim($input['descripcion']),
        trim($input['imagen'] ?? ''),
        trim($input['poder'] ?? 'Desconocido'),
        $id
    ];

    try {
        $stmt->execute($valores);

        // Verificar si se actualizó algún registro
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'error' => 'Guerrero no encontrado o sin cambios.']);
            exit;
        }

        echo json_encode(['success' => true, 'id' => $id, 'data' => $input]);
        exit;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Error al actualizar en la base de datos.']);
        exit;
    }
}


//  Eliminar guerrero

if ($metodo === 'DELETE') {
    $id = $_GET['id'] ?? '';

    // Validación de ID (debe ser un entero para la llave primaria)
    if (!filter_var($id, FILTER_VALIDATE_INT)) {
        echo json_encode(['success' => false, 'error' => 'ID de eliminación inválido.']);
        exit;
    }

    $sql = "DELETE FROM guerreros WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Guerrero no encontrado.']);
    }
    exit;
}
