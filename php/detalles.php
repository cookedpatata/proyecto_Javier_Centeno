<?php
include("conexion.php");
session_start();

/* ===============================
   VALIDAR ID DEL INMUEBLE
   =============================== */
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die("Inmueble no válido");
}

/* ===============================
   CONSULTA COMPLETA DEL INMUEBLE
   =============================== */
$sql = "
SELECT 
    i.id_inmueble,
    i.m2,
    i.direccion,
    i.precio,

    ti.categoria,
    ti.subcategoria AS tipo,

    p.nombre AS provincia,
    c.nombre AS comunidad,

    a.tipo AS tipo_operacion,

    cli.id_cliente AS id_cliente,
    cli.nom AS vendedor_nombre,
    cli.apellidos AS vendedor_apellidos,
    cli.tel AS vendedor_tel,
    cli.correo AS vendedor_correo,

    img.ruta AS imagen

FROM Inmuebles i
JOIN tipos_inmuebles ti 
    ON i.id_tipo = ti.id_tipo
JOIN provincias p 
    ON i.id_provincia = p.id_provincia
JOIN comunidades c 
    ON p.id_comunidad = c.id_comunidad
JOIN Anuncios a 
    ON a.id_inmueble = i.id_inmueble
JOIN Clientes cli 
    ON i.id_cliente = cli.id_cliente
LEFT JOIN Imagenes_Inmuebles ii 
    ON ii.id_inmueble = i.id_inmueble
LEFT JOIN imagenes img 
    ON img.id_imagen = ii.id_imagen
WHERE i.id_inmueble = ?

";

$resultado = consulta(
    $conexion,
    $sql,
    [
        'id_inmueble',
        'm2',
        'direccion',
        'precio',
        'categoria',
        'tipo',
        'provincia',
        'comunidad',
        'tipo_operacion',
        'id_cliente',
        'vendedor_nombre',
        'vendedor_apellidos',
        'vendedor_tel',
        'vendedor_correo',
        'imagen'
    ],
    [$id]
);

if (count($resultado) === 0) {
    die("Inmueble no encontrado");
}

/* ===============================
   SEPARAR DATOS E IMÁGENES
   =============================== */
$inmueble = $resultado[0];

$yaComprado = consulta(
    $conexion,
    "
    SELECT 1
    FROM Contratos c
    JOIN Contratos_Clientes cc 
        ON cc.id_contrato = c.id_contrato
    WHERE c.id_inmueble = ?
      AND cc.id_cliente = ?
    LIMIT 1
    ",
    ['existe'],
    [$inmueble['id_inmueble'], $_SESSION['usuario_id']]
);

$esComprador = count($yaComprado) > 0;

$imagenes = array_filter(array_unique(array_column($resultado, 'imagen')));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Detalle del inmueble</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>

<body>

    <header class="nav">
        <div class="logo">
            <a href="index.php">
                <img src="../img/logo.png" alt="Logo">
            </a>
        </div>
        <div class="navigation">
            <a href="index.php">Inicio</a>
            <a href="NuestrosInmuebles.php">Nuestros inmuebles</a>
            <a href="Cuenta.php">Cuenta</a>
        </div>
    </header>

    <main class="container my-4">

        <div class="detalle-grid">

            <!-- GALERÍA -->
            <section class="detalle-galeria">
                <?php if (count($imagenes) > 0): ?>
                    <?php foreach ($imagenes as $img): ?>
                        <img src="../img/<?= htmlspecialchars($img) ?>" class="img-galeria">
                    <?php endforeach; ?>
                <?php else: ?>
                    <img src="../img/inmueble_default.jpg" class="img-galeria">
                <?php endif; ?>
            </section>

            <!-- INFO -->
            <section class="detalle-info">

                <h2><?= htmlspecialchars($inmueble['tipo']) ?> en <?= htmlspecialchars($inmueble['provincia']) ?></h2>

                <p class="precio">
                    <?= number_format($inmueble['precio'], 0, ',', '.') ?> €
                </p>

                <p><?= htmlspecialchars($inmueble['direccion']) ?></p>
                <p><?= (int)$inmueble['m2'] ?> m² · <?= htmlspecialchars($inmueble['tipo_operacion']) ?></p>

                <hr>

                <h4>Vendedor</h4>
                <p>
                    <?= htmlspecialchars($inmueble['vendedor_nombre']) ?>
                    <?= htmlspecialchars($inmueble['vendedor_apellidos']) ?>
                </p>

                <p>📞 <?= htmlspecialchars($inmueble['vendedor_tel']) ?></p>
                <p>✉️ <?= htmlspecialchars($inmueble['vendedor_correo']) ?></p>

                <!-- BOTÓN CONTRATAR -->
                <?php if (isset($_SESSION['usuario_id'])): ?>

                    <?php if ($_SESSION['usuario_id'] == $inmueble['id_cliente']): ?>

                        <!-- BOTÓN EDITAR (ES EL VENDEDOR) -->
                        <a href="editar_inmueble.php?id=<?= $inmueble['id_inmueble'] ?>"
                            class="btn btn-warning mt-3">
                            ✏️ Modificar anuncio
                        </a>

                    <?php elseif ($esComprador): ?>
                        <p class="text-success">Ya has comprado este inmueble.</p>
                    <?php else: ?>

                        <!-- BOTÓN CONTRATAR (NO ES EL VENDEDOR) -->
                        <form method="POST">
                            <input type="hidden" name="id_vendedor" value="<?= $inmueble['id_cliente'] ?>">
                            <button type="submit" name="contratar" class="btn btn-primary mt-3">
                                Contactar / Contratar
                            </button>
                        </form>

                        <?php
                        if (isset($_POST['contratar'])) {

                            $id_cliente  = $_SESSION['usuario_id']; // comprador
                            $id_vendedor = $_POST['id_vendedor'];   // vendedor

                            // Trabajador aleatorio
                            $trabajador = consulta(
                                $conexion,
                                "SELECT id_trabajador FROM Trabajadores ORDER BY RAND() LIMIT 1",
                                ['id_trabajador']
                            );

                            if (count($trabajador) === 0) {
                                die("No hay trabajadores disponibles");
                            }

                            $id_trabajador = $trabajador[0]['id_trabajador'];

                            $id_inmueble = $inmueble['id_inmueble'];
                            // Crear contrato
                            $stmt = $conexion->prepare("
                INSERT INTO Contratos (fecha,id_inmueble,id_trabajador)
                VALUES (CURDATE(), ?, ?)
            ");
                            $stmt->execute([$id_inmueble, $id_trabajador]);

                            $id_contrato = $conexion->lastInsertId();

                            // Relacionar comprador y vendedor
                            $stmt = $conexion->prepare("
                INSERT INTO Contratos_Clientes (id_contrato, id_cliente, id_vendedor)
                VALUES (?, ?, ?)
            ");
                            $stmt->execute([$id_contrato, $id_cliente, $id_vendedor]);

                            $mensaje = "Solicitud enviada correctamente";
                        }
                        ?>

                    <?php endif; ?>

                <?php else: ?>
                    <p><em>Inicia sesión para contactar con el vendedor</em></p>
                <?php endif; ?>


            </section>

        </div>

    </main>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>