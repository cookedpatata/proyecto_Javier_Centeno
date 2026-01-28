<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <style>
        body {
            font-family: Arial;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f0f0f0;
        }

        .login-box {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        h2 {
            text-align: center;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 3px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <div>
        <h2>Registro de Cliente</h2>

        <?php
        if (isset($_SESSION['error'])) {
            echo "<p style='color:red'>" . $_SESSION['error'] . "</p>";
            unset($_SESSION['error']);
        }
        ?>

        <form action="registro.php" method="POST">
            <label>Nombre:</label><br>
            <input type="text" name="nom" required><br><br>

            <label>Apellidos:</label><br>
            <input type="text" name="apellidos" required><br><br>

            <label>DNI:</label><br>
            <input type="text" name="dni" required><br><br>

            <label>Teléfono:</label><br>
            <input type="text" name="tel"><br><br>

            <label>Correo:</label><br>
            <input type="email" name="correo" required><br><br>

            <label>Contraseña:</label><br>
            <input type="password" name="password" required><br><br>


            <input type="checkbox" name="es_trabajador" value="1">
            <center>Registrarme como trabajador</center>
            <br><br>

            <button type="submit">Registrarse</button>
        </form>

        <p>¿Ya tienes cuenta? <a href="Cuenta.php">Inicia sesión</a></p>
    </div>
    <?php
    include('conexion.php');

    $nom       = trim($_POST['nom'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $dni       = strtoupper(trim($_POST['dni'] ?? ''));
    $tel       = trim($_POST['tel'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $password  = trim($_POST['password'] ?? '');

    $esTrabajador = isset($_POST['es_trabajador']);

    /* ===============================
   VALIDACIÓN CAMPOS VACÍOS
   =============================== */
    if ($nom === '' || $apellidos === '' || $dni === '' || $correo === '' || $password === '') {
        $_SESSION['error'] = "Rellena todos los campos obligatorios";
        exit;
    }

    /* ===============================
   EXPRESIONES REGULARES
   =============================== */

    $regexNombre = '/^[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+$/u';
    $regexApellidos = '/^[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(\s[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)*$/u';
    $regexDNI = '/^[0-9]{8}[A-Z]$/';
    $regexTel = '/^[0-9]{9}$/';
    $regexCorreo = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
    $regexPass = '/^(?=.*[A-Z])(?=.*\d).{8,}$/';

    /* ===============================
   VALIDACIONES
   =============================== */

    if (!preg_match($regexNombre, $nom)) {
        $_SESSION['error'] = "El nombre debe empezar en mayúscula y el resto en minúscula";
        header('Location: registro.php');
        exit;
    }

    if (!preg_match($regexApellidos, $apellidos)) {
        $_SESSION['error'] = "Los apellidos deben empezar en mayúscula y el resto en minúscula";
        header('Location: registro.php');
        exit;
    }

    if (!preg_match($regexDNI, $dni)) {
        $_SESSION['error'] = "DNI no válido (formato 12345678A)";
        header('Location: registro.php');
        exit;
    }

    if (!preg_match($regexTel, $tel)) {
        $_SESSION['error'] = "Teléfono no válido";
        header('Location: registro.php');
        exit;
    }

    if (!preg_match($regexCorreo, $correo)) {
        $_SESSION['error'] = "Correo electrónico no válido";
        header('Location: registro.php');
        exit;
    }

    if (!preg_match($regexPass, $password)) {
        $_SESSION['error'] = "La contraseña debe tener al menos 8 caracteres, una letra y un número";
        header('Location: registro.php');
        exit;
    }

    /* ===============================
   COMPROBAR DUPLICADOS
   =============================== */

    $existeCorreo = consulta(
        $conexion,
        "SELECT correo FROM Clientes WHERE correo = ?",
        ['correo'],
        [$correo]
    );

    if (count($existeCorreo) > 0) {
        $_SESSION['error'] = "El correo ya está registrado";
        header('Location: registro.php');
        exit;
    }

    $existeDni = consulta(
        $conexion,
        "SELECT DNI FROM Clientes WHERE DNI = ?",
        ['DNI'],
        [$dni]
    );

    if (count($existeDni) > 0) {
        $_SESSION['error'] = "El DNI ya está registrado";
        header('Location: registro.php');
        exit;
    }

    /* ===============================
   REGISTRO OK
   =============================== */
    $passwordMD5 = md5($password);

    if ($esTrabajador) {

        // REGISTRAR TRABAJADOR
        $sql = "
        INSERT INTO Trabajadores (nom, apellidos, DNI, tel, correo, contraseña)
        VALUES (
            '$nom',
            '$apellidos',
            '$dni',
            '$tel',
            '$correo',
            '$passwordMD5'
        )
    ";
    } else {

        // REGISTRAR CLIENTE
        $sql = "
        INSERT INTO Clientes (nom, apellidos, DNI, tel, correo, contraseña)
        VALUES (
            '$nom',
            '$apellidos',
            '$dni',
            '$tel',
            '$correo',
            '$passwordMD5'
        )
    ";
    }

    insertar($conexion, $sql);
    if ($esTrabajador) {

        $idUsuario = consulta(
            $conexion,
            "SELECT id_trabajador 
         FROM Trabajadores 
         WHERE correo = ? AND contraseña = ?",
            ['id_trabajador'],
            [$correo, $passwordMD5]
        );

        $_SESSION['id_Usuario'] = $idUsuario[0]['id_trabajador'];
    } else {

        $idUsuario = consulta(
            $conexion,
            "SELECT id_cliente 
         FROM Clientes 
         WHERE correo = ? AND contraseña = ?",
            ['id_cliente'],
            [$correo, $passwordMD5]
        );

        $_SESSION['id_Usuario'] = $idUsuario[0]['id_cliente'];
    }
    header('Location: Cuenta.php');
    exit;

    ?>
</body>

</html>