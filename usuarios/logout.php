<?php
require_once '../comun/db.php';
session_start();
session_destroy();
header('Location: ../index.html');
exit;
