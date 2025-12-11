<?php


const DB_HOST = "localhost";
const DB_USER = "root";
const DB_PASS = "";
const DB_NAME = "dbz_guerreros";

// funcion para conectar a la base de datos
function conectarDb(): PDO
{
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de base de datos. Crea la base dbz_guerreros e importa el SQL.");
    }
}

// funcion para escapar datos y evitar XSS
function h($str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
