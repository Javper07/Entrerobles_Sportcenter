<?php
ini_set('session.save_handler', 'files');
ini_set('session.save_path', 'C:\\xampp\\tmp');
session_start();

if (isset($_GET['set'])) {
    $_SESSION['usuario_id'] = 99;
    echo "Sesión guardada. ID de sesión: " . session_id();
}

if (isset($_GET['check'])) {
    if (isset($_SESSION['usuario_id'])) {
        echo "✓ FUNCIONA - usuario_id=" . $_SESSION['usuario_id'];
    } else {
        echo "✗ NO FUNCIONA - sesión vacía. ID: " . session_id();
    }
}

echo "<br>Save path: " . session_save_path();
echo "<br>Tmp existe: " . (is_dir('C:\\xampp\\tmp') ? 'SI' : 'NO');
?>