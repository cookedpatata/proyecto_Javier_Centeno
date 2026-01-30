<?php
include("conexion.php");
include("validaciones.php");
session_start();

/* ===============================
   PROCESAR LOGIN
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $correo   = trim($_POST['correo'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($correo === '' || $password === '') {
        $_SESSION['error'] = "Rellena todos los campos";
        header('Location: Cuenta.php');
        exit;
    }

    if (!validarCorreo($correo)) {
        $_SESSION['error'] = "Correo no válido";
        header('Location: Cuenta.php');
        exit;
    }

    if (!validarPassword($password)) {
        $_SESSION['error'] = "Contraseña inválida";
        header('Location: Cuenta.php');
        exit;
    }

  $passwordMD5 = md5($password);

  /* BUSCAR EN CLIENTES */
  $clientes = consulta(
    $conexion,
    "SELECT id_cliente FROM Clientes WHERE correo = ? AND contraseña = ?",
    ['id_cliente'],
    [$correo, $passwordMD5]
  );

  if (count($clientes) > 0) {
    $_SESSION['usuario_id'] = $clientes[0]['id_cliente'];
    $_SESSION['rol'] = 'cliente';
    header('Location: cliente.php');
    exit;
  }

  /* BUSCAR EN TRABAJADORES */
  $trabajadores = consulta(
    $conexion,
    "SELECT id_trabajador FROM Trabajadores WHERE correo = ? AND contraseña = ?",
    ['id_trabajador'],
    [$correo, $passwordMD5]
  );

  if (count($trabajadores) > 0) {

    $id_trabajador = $trabajadores[0]['id_trabajador'];

    $_SESSION['usuario_id'] = $id_trabajador;
    $_SESSION['rol'] = 'trabajador';

    // 🔐 SI ES ADMIN (id = 1)
    if ($id_trabajador == 1) {
      $_SESSION['rol'] = 'admin';
      header('Location: admin.php');
      exit;
    } else {
      header('Location: trabajador.php');
      exit;
    }
  }

  $_SESSION['error'] = "Credenciales incorrectas";
  header('Location: Cuenta.php');
  exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <title>Inmobiliaria - Venta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../styles.css">
</head>

<body>

  <header class="nav">
    <div class="logo">
      <a href="../php/index.php">
        <img src="../img/logo.png" alt="Logo">
      </a>
    </div>
    <div class="navigation">
      <a href="../php/index.php">Inicio</a>
      <a href="../php/NuestrosInmuebles.php">Nuestros inmuebles</a>
      <a href="../php/Cuenta.php">Cuenta</a>

    </div>
  </header>

  <div class="containerInicio">

    <?php if (!isset($_SESSION['usuario_id'])): ?>

      <h3>Iniciar sesión</h3>

      <?php
      if (isset($_SESSION['error'])) {
        echo "<p style='color:red'>" . $_SESSION['error'] . "</p>";
        unset($_SESSION['error']);
      }
      ?>

      <form method="POST">
        <label>Correo:</label><br>
        <input type="email" name="correo" required><br><br>

        <label>Contraseña:</label><br>
        <input type="password" name="password" required><br><br>

        <button class="btn btn-success">Entrar</button>
      </form>

      <p>¿Aún no tienes cuenta?</p>
      <a href="Registro.php" class="btn btn-primary">Regístrate</a>

    <?php else:

      if ($_SESSION['rol'] === 'cliente'):
        header('Location: cliente.php');
      elseif ($_SESSION['rol'] === 'trabajador'):
        header('Location: trabajador.php');
      elseif ($_SESSION['rol'] === 'admin'):
        header('Location: admin.php');
      endif;

    endif; ?>

  </div>

</body>

</html>