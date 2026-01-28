<?php
session_start();
include("conexion.php");

/* ===============================
   COMPROBAR SESIÓN
   =============================== */
if (!isset($_SESSION['usuario_id'])) {
    die("Acceso no autorizado");
}

$id_usuario = $_SESSION['usuario_id'];

/* ===============================
   VALIDAR ID DEL INMUEBLE
   =============================== */
$id_inmueble = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_inmueble) {
    die("Inmueble no válido");
}

/* ===============================
   OBTENER DATOS DEL INMUEBLE
   (y comprobar que es del usuario)
   =============================== */
$datos = consulta(
    $conexion,
    "
    SELECT 
        i.id_inmueble,
        i.m2,
        i.direccion,
        i.precio,
        i.id_provincia,
        i.id_tipo,
        a.tipo AS tipo_operacion,
        img.id_imagen,
        img.ruta AS imagen
    FROM Inmuebles i
    JOIN Anuncios a ON a.id_inmueble = i.id_inmueble
    LEFT JOIN Imagenes_Inmuebles ii ON ii.id_inmueble = i.id_inmueble
    LEFT JOIN imagenes img ON img.id_imagen = ii.id_imagen
    WHERE i.id_inmueble = ?
      AND i.id_cliente = ?
    ",
    [
        'id_inmueble',
        'm2',
        'direccion',
        'precio',
        'id_provincia',
        'id_tipo',
        'tipo_operacion',
        'id_imagen',
        'imagen'
    ],
    [$id_inmueble, $id_usuario]
);

if (count($datos) === 0) {
    die("No tienes permiso para editar este inmueble");
}

$inmueble = $datos[0];

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
   RUTA IMÁGENES
   =============================== */
$carpetaImagenes = realpath(__DIR__ . "/../img") . "/";

/* ===============================
   PROCESAR EDICIÓN
   =============================== */
if (isset($_POST['guardar'])) {

    $direccion    = $_POST['direccion'];
    $m2           = $_POST['m2'];
    $precio       = $_POST['precio'];
    $id_provincia = $_POST['id_provincia'];
    $id_tipo      = $_POST['id_tipo'];
    $tipo_op      = $_POST['tipo_operacion'];

    try {
        $conexion->beginTransaction();

        /* 1️⃣ Actualizar inmueble */
        $stmt = $conexion->prepare("
            UPDATE Inmuebles
            SET direccion = ?, m2 = ?, precio = ?, id_provincia = ?, id_tipo = ?
            WHERE id_inmueble = ?
        ");
        $stmt->execute([
            $direccion,
            $m2,
            $precio,
            $id_provincia,
            $id_tipo,
            $id_inmueble
        ]);

        /* 2️⃣ Actualizar anuncio */
        $stmt = $conexion->prepare("
            UPDATE Anuncios
            SET tipo = ?
            WHERE id_inmueble = ?
        ");
        $stmt->execute([$tipo_op, $id_inmueble]);

        /* 3️⃣ Cambiar imagen (opcional) */
        if (!empty($_FILES['imagen']['name'])) {

            $imagen = $_FILES['imagen'];

            if ($imagen['error'] === UPLOAD_ERR_OK) {

                $extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
                $nombreImagen = uniqid("inmueble_") . "." . $extension;
                $rutaFisica = $carpetaImagenes . $nombreImagen;

                move_uploaded_file($imagen['tmp_name'], $rutaFisica);

                // Insertar nueva imagen
                $stmt = $conexion->prepare("
                    INSERT INTO imagenes (ruta)
                    VALUES (?)
                ");
                $stmt->execute([$nombreImagen]);

                $id_imagen = $conexion->lastInsertId();

                // Eliminar relación anterior
                $conexion->prepare("
                    DELETE FROM Imagenes_Inmuebles
                    WHERE id_inmueble = ?
                ")->execute([$id_inmueble]);

                // Relacionar nueva imagen
                $conexion->prepare("
                    INSERT INTO Imagenes_Inmuebles (id_imagen, id_inmueble)
                    VALUES (?, ?)
                ")->execute([$id_imagen, $id_inmueble]);
            }
        }

        $conexion->commit();
        $mensaje = "Anuncio actualizado correctamente";
    } catch (PDOException $e) {
        $conexion->rollBack();
        $mensaje = "Error al actualizar el anuncio";
    }
}
if (isset($_POST['borrar'])) {

    try {
        $conexion->beginTransaction();

        /* 1️⃣ Obtener imágenes asociadas */
        $imagenes = consulta(
            $conexion,
            "
            SELECT img.id_imagen, img.ruta
            FROM Imagenes_Inmuebles ii
            JOIN imagenes img ON img.id_imagen = ii.id_imagen
            WHERE ii.id_inmueble = ?
            ",
            ['id_imagen', 'ruta'],
            [$id_inmueble]
        );

        /* 2️⃣ Borrar relaciones imagen-inmueble */
        $conexion->prepare("
            DELETE FROM Imagenes_Inmuebles
            WHERE id_inmueble = ?
        ")->execute([$id_inmueble]);

        /* 3️⃣ Borrar imágenes (BD + archivo) */
        foreach ($imagenes as $img) {

            // borrar archivo físico
            $rutaFisica = $carpetaImagenes . $img['ruta'];
            if (file_exists($rutaFisica)) {
                unlink($rutaFisica);
            }

            // borrar registro imagen
            $conexion->prepare("
                DELETE FROM imagenes
                WHERE id_imagen = ?
            ")->execute([$img['id_imagen']]);
        }

        /* 4️⃣ Borrar contratos relacionados (si existieran) */
        $contratos = consulta(
            $conexion,
            "SELECT id_contrato FROM Contratos WHERE id_inmueble = ?",
            ['id_contrato'],
            [$id_inmueble]
        );

        foreach ($contratos as $c) {
            $conexion->prepare("
                DELETE FROM Contratos_Clientes
                WHERE id_contrato = ?
            ")->execute([$c['id_contrato']]);
        }

        $conexion->prepare("
            DELETE FROM Contratos
            WHERE id_inmueble = ?
        ")->execute([$id_inmueble]);

        /* 5️⃣ Borrar anuncio */
        $conexion->prepare("
            DELETE FROM Anuncios
            WHERE id_inmueble = ?
        ")->execute([$id_inmueble]);

        /* 6️⃣ Borrar inmueble */
        $conexion->prepare("
            DELETE FROM Inmuebles
            WHERE id_inmueble = ?
        ")->execute([$id_inmueble]);

        $conexion->commit();

        // Redirigir fuera del detalle
        header("Location: Cuenta.php");
        exit;
    } catch (PDOException $e) {
        $conexion->rollBack();
        $mensaje = "Error al borrar el inmueble";
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar inmueble</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>

<body>

    <header class="nav">
        <div class="logo">
            <a href="index.php"><img src="../img/logo.png"></a>
        </div>
        <div class="navigation">
            <a href="index.php">Inicio</a>
            <a href="Cuenta.php">Cuenta</a>
        </div>
    </header>

    <main class="container my-4">

        <h2>Editar anuncio</h2>

        <?php if (isset($mensaje)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-inmueble">

            <img src="../img/<?= htmlspecialchars($inmueble['imagen'] ?: 'inmueble_default.jpg') ?>"
                class="mb-3" style="max-width:300px">

            <div class="mb-3">
                <label>Dirección</label>
                <input type="text" name="direccion" class="form-control"
                    value="<?= htmlspecialchars($inmueble['direccion']) ?>" required>
            </div>

            <div class="mb-3">
                <label>Metros cuadrados</label>
                <input type="number" step="0.01" name="m2" class="form-control"
                    value="<?= $inmueble['m2'] ?>" required>
            </div>

            <div class="mb-3">
                <label>Precio (€)</label>
                <input type="number" step="0.01" name="precio" class="form-control"
                    value="<?= $inmueble['precio'] ?>" required>
            </div>

            <div class="mb-3">
                <label>Provincia</label>
                <select name="id_provincia" class="form-select">
                    <?php foreach ($provincias as $p): ?>
                        <option value="<?= $p['id_provincia'] ?>"
                            <?= $p['id_provincia'] == $inmueble['id_provincia'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>Tipo de inmueble</label>
                <select name="id_tipo" class="form-select">
                    <?php foreach ($tipos as $t): ?>
                        <option value="<?= $t['id_tipo'] ?>"
                            <?= $t['id_tipo'] == $inmueble['id_tipo'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['subcategoria']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>Tipo de operación</label>
                <select name="tipo_operacion" class="form-select">
                    <option value="venta" <?= $inmueble['tipo_operacion'] === 'venta' ? 'selected' : '' ?>>Venta</option>
                    <option value="alquiler" <?= $inmueble['tipo_operacion'] === 'alquiler' ? 'selected' : '' ?>>Alquiler</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Cambiar imagen</label>
                <input type="file" name="imagen" class="form-control" accept="image/*">
            </div>

            <button type="submit" name="guardar" class="btn btn-success">
                Guardar cambios
            </button>

            <button type="submit"
                name="borrar"
                class="btn btn-danger mt-3"
                onclick="return confirm('¿Seguro que quieres borrar este inmueble? Esta acción no se puede deshacer');">
                🗑️ Borrar inmueble
            </button>


            <a href="detalles.php?id=<?= $id_inmueble ?>" class="btn btn-secondary ms-2">
                Volver
            </a>

        </form>

    </main>

</body>

</html>