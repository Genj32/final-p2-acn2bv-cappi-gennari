<?php


require_once 'Guerrero.php';
header('Content-Type: application/json; charset=utf-8');

// instancia del objeto Guerrero
$g = new Guerrero();


$method = $_SERVER['REQUEST_METHOD'];
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
