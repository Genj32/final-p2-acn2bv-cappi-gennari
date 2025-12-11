<?php

require_once 'Guerrero.php';
header('Content-Type: application/json; charset=utf-8');

// instancia del objeto Guerrero
$g = new Guerrero();


$metodo = $_SERVER['REQUEST_METHOD'];

 //  metodo que devuelva la lista de guerreros con filtros y paginacion
if ($metodo === 'GET') {
    $buscar = $_GET['buscar'] ?? '';
    $raza   = $_GET['raza']   ?? '';
    $pagina = max(1, (int)($_GET['pagina'] ?? 1));

    $guerreros = $g->todos($buscar, $raza, $pagina);
    $total     = $g->total($buscar, $raza);
    $paginas   = ceil($total / 7); 

    echo json_encode([
        'success' => true,
        'data'    => $guerreros,
        'paginacion' => [
            'total'        => $total,
            'paginas'      => $paginas,
            'actual'       => $pagina,
            'por_pagina'   => 7
        ]
    ]);
    exit;
}

// este metodo lee los datos enviados en el cuerpo de la solicitud
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// metodo para crear un nuevo guerrero
if ($method === 'POST') {
    if (empty($input['nombre']) || empty($input['raza']) || empty($input['descripcion'])) {
        echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
        exit;
    }
    $id = $g->crear($input);
    echo json_encode(['success' => true, 'id' => $id, 'data' => $input + ['id' => $id]]);
    exit;
}



// metodo para actualizar un guerrero 
if ($method === 'PUT' && isset($_GET['id'])) {
    $g->actualizar($_GET['id'], $input);
    echo json_encode(['success' => true]);
    exit;
}


// metodo para eliminar un guerrero
if ($method === 'DELETE' && isset($_GET['id'])) {
    $g->eliminar($_GET['id']);
    echo json_encode(['success' => true]);
    exit;
}
