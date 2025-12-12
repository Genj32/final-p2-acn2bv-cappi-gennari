<?php
$tema = $_GET['tema'] ?? 'claro';
$clase = $tema === 'oscuro' ? 'tema-oscuro' : '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DBZ - Guerreros Z</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="Main.js" defer></script>
</head>

<body class="<?= $clase ?>">
    <header>
        <h1>Dragon Ball Z – Guerreros</h1>
        <span class="muted">Tema: <strong><?= ucfirst($tema) ?></strong></span>
    </header>    
 </body>