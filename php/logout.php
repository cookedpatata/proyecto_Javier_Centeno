<?php
session_start();

$_SESSION['usuario_id'] = null;

header('Location: Cuenta.php');
exit;
