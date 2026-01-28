<?php
session_start();
include("conexion.php");

/* ===============================
   COMPROBAR SESIÓN
   =============================== */
if (!isset($_SESSION['usuario_id'])) {
    die("Debes iniciar sesión");
}

$id_cliente = $_SESSION['usuario_id'];

/* ===============================
   RUTA IMÁGENES
   =============================== */
$carpetaImagenes = realpath(__DIR__ . "/../img") . "/";

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
   INSERTAR INMUEBLE + ANUNCIO + IMAGEN
   =============================== */
if (isset($_POST['publicar'])) {

    $direccion    = $_POST['direccion'] ?? '';
    $m2           = $_POST['m2'] ?? '';
    $precio       = $_POST['precio'] ?? '';
    $id_provincia = $_POST['id_provincia'] ?? '';
    $id_tipo      = $_POST['id_tipo'] ?? '';
    $tipo_op      = $_POST['tipo_operacion'] ?? '';

    if (
        $direccion === '' ||
        !is_numeric($m2) ||
        !is_numeric($precio) ||
        !is_numeric($id_provincia) ||
        !is_numeric($id_tipo) ||
        !isset($_FILES['imagen'])
    ) {
        $mensaje = "Datos inválidos";
    } else {

        try {
            $conexion->beginTransaction();

            /* 1️⃣ Insertar inmueble */
            $stmt = $conexion->prepare("
                INSERT INTO Inmuebles
                (m2, direccion, precio, id_cliente, id_provincia, id_tipo)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $m2,
                $direccion,
                $precio,
                $id_cliente,
                $id_provincia,
                $id_tipo
            ]);

            $id_inmueble = $conexion->lastInsertId();

            /* 2️⃣ Crear anuncio */
            $stmt = $conexion->prepare("
                INSERT INTO Anuncios (tipo, id_cliente, id_inmueble)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $tipo_op,
                $id_cliente,
                $id_inmueble
            ]);

            /* 3️⃣ Subir imagen */
            $imagen = $_FILES['imagen'];

            if ($imagen['error'] === UPLOAD_ERR_OK) {

                $extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
                $nombreImagen = uniqid("inmueble_") . "." . $extension;
                $rutaFisica = $carpetaImagenes . $nombreImagen;

                move_uploaded_file($imagen['tmp_name'], $rutaFisica);

                /* 4️⃣ Insertar imagen */
                $stmt = $conexion->prepare("
                    INSERT INTO imagenes (ruta)
                    VALUES (?)
                ");
                $stmt->execute([$nombreImagen]);

                $id_imagen = $conexion->lastInsertId();

                /* 5️⃣ Relacionar imagen con inmueble */
                $stmt = $conexion->prepare("
                    INSERT INTO Imagenes_Inmuebles (id_imagen, id_inmueble)
                    VALUES (?, ?)
                ");
                $stmt->execute([$id_imagen, $id_inmueble]);
            }

            $conexion->commit();
            $mensaje = "Inmueble publicado correctamente";
        } catch (PDOException $e) {
            $conexion->rollBack();
            $mensaje = "Error al publicar el inmueble";
        }
    }
}

/* ===============================
   INMUEBLES EN VENTA DEL CLIENTE
   =============================== */
$mis_inmuebles = consulta(
    $conexion,
    "
    SELECT 
        i.id_inmueble,
        i.m2,
        i.direccion,
        i.precio,
        ti.subcategoria AS tipo,
        p.nombre AS provincia,
        c.nombre AS comunidad,
        img.ruta AS imagen
    FROM Inmuebles i
    JOIN tipos_inmuebles ti ON i.id_tipo = ti.id_tipo
    JOIN provincias p ON i.id_provincia = p.id_provincia
    JOIN comunidades c ON p.id_comunidad = c.id_comunidad
    JOIN Anuncios a ON a.id_inmueble = i.id_inmueble
    LEFT JOIN Imagenes_Inmuebles ii ON ii.id_inmueble = i.id_inmueble
    LEFT JOIN imagenes img ON img.id_imagen = ii.id_imagen
    LEFT JOIN Contratos co ON co.id_inmueble = i.id_inmueble
    WHERE i.id_cliente = ?
      AND a.tipo = 'venta'
      AND co.id_contrato IS NULL
    ORDER BY i.id_inmueble DESC
    ",
    ['id_inmueble', 'm2', 'direccion', 'precio', 'tipo', 'provincia', 'comunidad', 'imagen'],
    [$id_cliente]
);

/* ===============================
   INMUEBLES COMPRADOS POR EL CLIENTE
   =============================== */
$inmuebles_comprados = consulta(
    $conexion,
    "
    SELECT DISTINCT
        i.id_inmueble,
        i.m2,
        i.direccion,
        i.precio,
        ti.subcategoria AS tipo,
        p.nombre AS provincia,
        c.nombre AS comunidad,
        img.ruta AS imagen,
        co.fecha AS fecha_compra
    FROM Contratos_Clientes cc
    JOIN Contratos co 
        ON cc.id_contrato = co.id_contrato
    JOIN Inmuebles i 
        ON co.id_inmueble = i.id_inmueble
    JOIN tipos_inmuebles ti 
        ON i.id_tipo = ti.id_tipo
    JOIN provincias p 
        ON i.id_provincia = p.id_provincia
    JOIN comunidades c 
        ON p.id_comunidad = c.id_comunidad
    LEFT JOIN Imagenes_Inmuebles ii 
        ON ii.id_inmueble = i.id_inmueble
    LEFT JOIN imagenes img 
        ON img.id_imagen = ii.id_imagen
    WHERE cc.id_cliente = ?
    ORDER BY co.fecha DESC
    ",
    [
        'id_inmueble',
        'm2',
        'direccion',
        'precio',
        'tipo',
        'provincia',
        'comunidad',
        'imagen',
        'fecha_compra'
    ],
    [$_SESSION['usuario_id']]
);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis inmuebles</title>
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
            <a href="NuestrosInmuebles.php">Nuestros inmuebles</a>
            <a href="Cuenta.php">Cuenta</a>
            <a href="logout.php" class="btn btn-danger">Cerrar sesión</a>

        </div>
    </header>

    <main class="container my-4">

        <h2>Publicar nuevo inmueble</h2>

        <?php if (isset($mensaje)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <form method="POST" enctype="multipart/form-data" class="form-inmueble mb-5">

            <div class="mb-3">
                <label class="form-label">Imagen del inmueble</label>
                <input type="file" name="imagen" class="form-control" accept="image/*" required>
            </div>

            <input type="text" name="direccion" class="form-control mb-2" placeholder="Dirección" required>
            <input type="number" name="m2" step="0.01" class="form-control mb-2" placeholder="Metros cuadrados" required>
            <input type="number" name="precio" step="0.01" class="form-control mb-2" placeholder="Precio" required>

            <select name="id_provincia" class="form-select mb-2">
                <?php foreach ($provincias as $p): ?>
                    <option value="<?= $p['id_provincia'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="id_tipo" class="form-select mb-2">
                <?php foreach ($tipos as $t): ?>
                    <option value="<?= $t['id_tipo'] ?>"><?= htmlspecialchars($t['subcategoria']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="tipo_operacion" class="form-select mb-3">
                <option value="venta">Venta</option>
                <option value="alquiler">Alquiler</option>
            </select>

            <button type="submit" name="publicar" class="btn btn-primary">
                Publicar inmueble
            </button>
        </form>

        <hr>

        <!-- LISTADO -->
        <h3>Mis inmuebles en venta</h3>

        <div class="inmuebles">
            <?php if (count($mis_inmuebles) === 0): ?>
                <p>No tienes inmuebles en venta.</p>
            <?php endif; ?>

            <?php foreach ($mis_inmuebles as $inmueble): ?>
                <div class="tarjeta-inmueble" data-id="<?= $inmueble['id_inmueble'] ?>">
                    <img src="../img/<?= htmlspecialchars($inmueble['imagen'] ?: 'inmueble_default.jpg') ?>">

                    <div class="card-body">
                        <h3 class="precio">
                            <?= number_format($inmueble['precio'], 0, ',', '.') ?> €
                        </h3>
                        <p><?= htmlspecialchars($inmueble['direccion']) ?></p>
                        <div class="datos">
                            <span>🏠 <?= htmlspecialchars($inmueble['tipo']) ?></span>
                            <span>📐 <?= (int)$inmueble['m2'] ?> m²</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <hr class="my-5">

        <h3>Inmuebles comprados</h3>

        <div class="inmuebles">
            <?php if (count($inmuebles_comprados) === 0): ?>
                <p>No has comprado ningún inmueble todavía.</p>
            <?php endif; ?>

            <?php foreach ($inmuebles_comprados as $inmueble): ?>
                <div class="tarjeta-inmueble" data-id="<?= $inmueble['id_inmueble'] ?>">

                    <img src="../img/<?= htmlspecialchars($inmueble['imagen'] ?: 'inmueble_default.jpg') ?>">

                    <div class="card-body">
                        <h3 class="precio">
                            <?= number_format($inmueble['precio'], 0, ',', '.') ?> €
                        </h3>

                        <p class="direccion">
                            <?= htmlspecialchars($inmueble['direccion']) ?>
                        </p>

                        <div class="datos">
                            <span>🏠 <?= htmlspecialchars($inmueble['tipo']) ?></span>
                            <span>📐 <?= (int)$inmueble['m2'] ?> m²</span>
                        </div>

                        <small class="text-muted">
                            Comprado el <?= date('d/m/Y', strtotime($inmueble['fecha_compra'])) ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>


    </main>

    <script>
        document.querySelectorAll(".tarjeta-inmueble").forEach(card => {
            card.addEventListener("click", () => {
                window.location.href = "detalles.php?id=" + card.dataset.id;
            });
        });
    </script>

</body>

</html>