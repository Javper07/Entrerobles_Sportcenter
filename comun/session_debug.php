<?php
ini_set('session.cache_limiter', 'nocache');
session_start();

echo "<pre>";
echo "SESSION ID: " . session_id() . "\n";
echo "SESSION STATUS: " . session_status() . "\n";
echo "SESSION SAVE PATH: " . session_save_path() . "\n";
echo "SESSION DATA: ";
print_r($_SESSION);

if (isset($_GET['set'])) {
    $_SESSION['usuario_id'] = 99;
    $_SESSION['test'] = 'funcionando';
    echo "\n✓ Sesión guardada con usuario_id=99\n";
}

if (isset($_GET['check'])) {
    if (isset($_SESSION['usuario_id'])) {
        echo "\n✓ SESIÓN ENCONTRADA: usuario_id=" . $_SESSION['usuario_id'] . "\n";
    } else {
        echo "\n✗ SESIÓN NO ENCONTRADA\n";
    }
}
echo "</pre>";
