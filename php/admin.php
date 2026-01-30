<?php
session_start();
include("conexion.php");
include("validaciones.php");

/* ===============================
   PROTECCIÓN ADMIN
   =============================== */
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_id'] != 1) {
    die("Acceso restringido");
}

/* ===============================
   ELIMINAR
   =============================== */
if (isset($_GET['delete_cliente'])) {
    $conexion->prepare("DELETE FROM Clientes WHERE id_cliente = ?")
        ->execute([(int)$_GET['delete_cliente']]);
}

if (isset($_GET['delete_trabajador'])) {
    $conexion->prepare("DELETE FROM Trabajadores WHERE id_trabajador = ?")
        ->execute([(int)$_GET['delete_trabajador']]);
}

if (isset($_GET['delete_inmueble'])) {
    $conexion->prepare("DELETE FROM Inmuebles WHERE id_inmueble = ?")
        ->execute([(int)$_GET['delete_inmueble']]);
}

/* ===============================
   CREAR CLIENTE
   =============================== */
if (isset($_POST['crear_cliente'])) {

    if (
        !validarNombre($_POST['nom']) ||
        !validarApellidos($_POST['apellidos']) ||
        !validarDNI($_POST['dni']) ||
        !validarCorreo($_POST['correo']) ||
        !validarPassword($_POST['password'])
    ) {
        $error = "Datos del cliente inválidos";
    } else {
        insertar($conexion, "
            INSERT INTO Clientes (nom, apellidos, DNI, tel, correo, contraseña)
            VALUES (
                '{$_POST['nom']}',
                '{$_POST['apellidos']}',
                '{$_POST['dni']}',
                '{$_POST['tel']}',
                '{$_POST['correo']}',
                MD5('{$_POST['password']}')
            )
        ");
    }
}

/* ===============================
   EDITAR CLIENTE
   =============================== */
if (isset($_POST['editar_cliente'])) {

    if (
        !validarNombre($_POST['nom']) ||
        !validarApellidos($_POST['apellidos']) ||
        !validarCorreo($_POST['correo'])
    ) {
        $error = "Datos del cliente no válidos";
    } else {
        insertar(
            $conexion,
            "
            UPDATE Clientes
            SET
                nom = '{$_POST['nom']}',
                apellidos = '{$_POST['apellidos']}',
                correo = '{$_POST['correo']}'
            WHERE id_cliente = " . (int)$_POST['id_cliente']
        );
    }
}

/* ===============================
   CREAR TRABAJADOR
   =============================== */
if (isset($_POST['crear_trabajador'])) {

    if (
        !validarNombre($_POST['nom']) ||
        !validarApellidos($_POST['apellidos']) ||
        !validarDNI($_POST['dni']) ||
        !validarCorreo($_POST['correo']) ||
        !validarPassword($_POST['password'])
    ) {
        $error = "Datos del trabajador inválidos";
    } else {
        insertar($conexion, "
            INSERT INTO Trabajadores (nom, apellidos, DNI, tel, correo, contraseña)
            VALUES (
                '{$_POST['nom']}',
                '{$_POST['apellidos']}',
                '{$_POST['dni']}',
                '{$_POST['tel']}',
                '{$_POST['correo']}',
                MD5('{$_POST['password']}')
            )
        ");
    }
}

/* ===============================
   EDITAR TRABAJADOR
   =============================== */
if (isset($_POST['editar_trabajador'])) {

    if (
        !validarNombre($_POST['nom']) ||
        !validarApellidos($_POST['apellidos']) ||
        !validarCorreo($_POST['correo'])
    ) {
        $error = "Datos del trabajador no válidos";
    } else {
        insertar(
            $conexion,
            "
            UPDATE Trabajadores
            SET
                nom = '{$_POST['nom']}',
                apellidos = '{$_POST['apellidos']}',
                correo = '{$_POST['correo']}'
            WHERE id_trabajador = " . (int)$_POST['id_trabajador']
        );
    }
}

/* ===============================
   EDITAR INMUEBLE
   =============================== */
if (isset($_POST['editar_inmueble'])) {

    if (
        !validarDireccion($_POST['direccion']) ||
        !validarPrecio($_POST['precio']) ||
        !validarMetros($_POST['m2']) ||
        !validarId($_POST['id_provincia']) ||
        !validarId($_POST['id_tipo'])
    ) {
        $error = "Datos del inmueble inválidos";
    } else {
        insertar(
            $conexion,
            "
            UPDATE Inmuebles
            SET
                direccion = '{$_POST['direccion']}',
                precio = " . (float)$_POST['precio'] . ",
                m2 = " . (float)$_POST['m2'] . ",
                id_provincia = " . (int)$_POST['id_provincia'] . ",
                id_tipo = " . (int)$_POST['id_tipo'] . "
            WHERE id_inmueble = " . (int)$_POST['id_inmueble']
        );
    }
}

/* ===============================
   DATOS PARA SELECTS
   =============================== */
$tipos = consulta(
    $conexion,
    "SELECT id_tipo, subcategoria FROM tipos_inmuebles ORDER BY subcategoria",
    ['id_tipo', 'subcategoria']
);

$provincias = consulta(
    $conexion,
    "SELECT id_provincia, nombre FROM provincias ORDER BY nombre",
    ['id_provincia', 'nombre']
);

/* ===============================
   LISTADOS
   =============================== */
$clientes = consulta(
    $conexion,
    "SELECT id_cliente, nom, apellidos, correo FROM Clientes",
    ['id_cliente', 'nom', 'apellidos', 'correo']
);

$trabajadores = consulta(
    $conexion,
    "SELECT id_trabajador, nom, apellidos, correo FROM Trabajadores",
    ['id_trabajador', 'nom', 'apellidos', 'correo']
);

$inmuebles = consulta(
    $conexion,
    "
    SELECT
        i.id_inmueble,
        i.direccion,
        i.precio,
        i.m2,
        i.id_provincia,
        i.id_tipo,
        c.nom AS propietario
    FROM Inmuebles i
    JOIN Clientes c ON i.id_cliente = c.id_cliente
    ORDER BY i.id_inmueble DESC
    ",
    ['id_inmueble', 'direccion', 'precio', 'm2', 'id_provincia', 'id_tipo', 'propietario']
);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>

<body class="container my-4">

    <header class="nav">
        <div class="logo">
            <a href="index.php"><img src="../img/logo.png"></a>
        </div>
        <div class="navigation">
            <a href="index.php">Inicio</a>
            <a href="NuestrosInmuebles.php">Nuestros inmuebles</a>
            <a href="Cuenta.php">Cuenta</a>
            <a href="logout.php" class="btn btn-danger">Cerrar sesión</a>

        </div>
    </header>

    <h1 class="mb-4">Panel de Administración</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <hr>

    <!-- ===============================
     CREAR CLIENTE
     =============================== -->
    <h3>Crear cliente</h3>
    <form method="POST" class="row g-2 mb-4">
        <input name="nom" class="form-control col" placeholder="Nombre" required>
        <input name="apellidos" class="form-control col" placeholder="Apellidos" required>
        <input name="dni" class="form-control col" placeholder="DNI" required>
        <input name="tel" class="form-control col" placeholder="Teléfono">
        <input name="correo" type="email" class="form-control col" placeholder="Correo" required>
        <input name="password" type="password" class="form-control col" placeholder="Contraseña" required>
        <button name="crear_cliente" class="btn btn-success col-auto">Crear</button>
    </form>

    <!-- ===============================
     LISTADO / EDICIÓN CLIENTES
     =============================== -->
    <h3>Clientes</h3>
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Correo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clientes as $c): ?>
                <tr>
                    <form method="POST">
                        <td><?= $c['id_cliente'] ?></td>
                        <td>
                            <input name="nom" class="form-control" value="<?= htmlspecialchars($c['nom']) ?>" required>
                        </td>
                        <td>
                            <input name="apellidos" class="form-control" value="<?= htmlspecialchars($c['apellidos']) ?>" required>
                        </td>
                        <td>
                            <input name="correo" type="email" class="form-control" value="<?= htmlspecialchars($c['correo']) ?>" required>
                        </td>
                        <td>
                            <input type="hidden" name="id_cliente" value="<?= $c['id_cliente'] ?>">
                            <button name="editar_cliente" class="btn btn-warning btn-sm">Guardar</button>
                            <a href="?delete_cliente=<?= $c['id_cliente'] ?>"
                                onclick="return confirm('¿Eliminar cliente?')"
                                class="btn btn-danger btn-sm">
                                Eliminar
                            </a>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <!-- ===============================
     CREAR TRABAJADOR
     =============================== -->
    <h3>Crear trabajador</h3>
    <form method="POST" class="row g-2 mb-4">
        <input name="nom" class="form-control col" placeholder="Nombre" required>
        <input name="apellidos" class="form-control col" placeholder="Apellidos" required>
        <input name="dni" class="form-control col" placeholder="DNI" required>
        <input name="tel" class="form-control col" placeholder="Teléfono">
        <input name="correo" type="email" class="form-control col" placeholder="Correo" required>
        <input name="password" type="password" class="form-control col" placeholder="Contraseña" required>
        <button name="crear_trabajador" class="btn btn-success col-auto">Crear</button>
    </form>

    <!-- ===============================
     LISTADO / EDICIÓN TRABAJADORES
     =============================== -->
    <h3>Trabajadores</h3>
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Correo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trabajadores as $t): ?>
                <tr>
                    <form method="POST">
                        <td><?= $t['id_trabajador'] ?></td>
                        <td>
                            <input name="nom" class="form-control" value="<?= htmlspecialchars($t['nom']) ?>" required>
                        </td>
                        <td>
                            <input name="apellidos" class="form-control" value="<?= htmlspecialchars($t['apellidos']) ?>" required>
                        </td>
                        <td>
                            <input name="correo" type="email" class="form-control" value="<?= htmlspecialchars($t['correo']) ?>" required>
                        </td>
                        <td>
                            <input type="hidden" name="id_trabajador" value="<?= $t['id_trabajador'] ?>">
                            <button name="editar_trabajador" class="btn btn-warning btn-sm">Guardar</button>
                            <?php if ($t['id_trabajador'] != 1): ?>
                                <a href="?delete_trabajador=<?= $t['id_trabajador'] ?>"
                                    onclick="return confirm('¿Eliminar trabajador?')"
                                    class="btn btn-danger btn-sm">
                                    Eliminar
                                </a>
                            <?php endif; ?>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <!-- ===============================
     LISTADO / EDICIÓN INMUEBLES
     =============================== -->
    <h3>Inmuebles</h3>
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Dirección</th>
                <th>Precio</th>
                <th>m²</th>
                <th>Provincia</th>
                <th>Tipo</th>
                <th>Propietario</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inmuebles as $i): ?>
                <tr>
                    <form method="POST">
                        <td><?= $i['id_inmueble'] ?></td>
                        <td>
                            <input name="direccion" class="form-control"
                                value="<?= htmlspecialchars($i['direccion']) ?>" required>
                        </td>
                        <td>
                            <input name="precio" type="number" step="0.01"
                                class="form-control" value="<?= $i['precio'] ?>" required>
                        </td>
                        <td>
                            <input name="m2" type="number" step="0.01"
                                class="form-control" value="<?= $i['m2'] ?>" required>
                        </td>
                        <td>
                            <select name="id_provincia" class="form-select" required>
                                <?php foreach ($provincias as $p): ?>
                                    <option value="<?= $p['id_provincia'] ?>"
                                        <?= $p['id_provincia'] == $i['id_provincia'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="id_tipo" class="form-select" required>
                                <?php foreach ($tipos as $t): ?>
                                    <option value="<?= $t['id_tipo'] ?>"
                                        <?= $t['id_tipo'] == $i['id_tipo'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['subcategoria']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><?= htmlspecialchars($i['propietario']) ?></td>
                        <td>
                            <input type="hidden" name="id_inmueble" value="<?= $i['id_inmueble'] ?>">
                            <button name="editar_inmueble" class="btn btn-warning btn-sm">
                                Guardar
                            </button>
                            <a href="?delete_inmueble=<?= $i['id_inmueble'] ?>"
                                onclick="return confirm('¿Eliminar inmueble?')"
                                class="btn btn-danger btn-sm">
                                Eliminar
                            </a>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>

</html>