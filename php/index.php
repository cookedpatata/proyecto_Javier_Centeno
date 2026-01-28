<?php
include("conexion.php");
session_start();

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['usuario_id'] = NULL;
}

/* ===============================
   DATOS PARA LOS SELECT
   =============================== */

// TIPOS DE INMUEBLE
$tipos = consulta(
    $conexion,
    "SELECT DISTINCT subcategoria FROM tipos_inmuebles ORDER BY subcategoria",
    ['subcategoria']
);

// PROVINCIAS
$provincias = consulta(
    $conexion,
    "SELECT id_provincia, nombre FROM provincias ORDER BY nombre",
    ['id_provincia', 'nombre']
);

// COMUNIDADES
$comunidades = consulta(
    $conexion,
    "SELECT id_comunidad, nombre FROM comunidades ORDER BY nombre",
    ['id_comunidad', 'nombre']
);

/* ===============================
   RECOGER FILTROS
   =============================== */
$tipoOperacion = $_POST['tipo_operacion'] ?? '';
$tipoInmueble  = $_POST['tipo_inmueble'] ?? '';
$comunidad     = $_POST['comunidad'] ?? '';
$provincia     = $_POST['provincia'] ?? '';

/* ===============================
   CONSULTA BASE
   =============================== */
$sql = "
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
JOIN tipos_inmuebles ti 
    ON i.id_tipo = ti.id_tipo
JOIN provincias p 
    ON i.id_provincia = p.id_provincia
JOIN comunidades c 
    ON p.id_comunidad = c.id_comunidad
JOIN Anuncios a 
    ON a.id_inmueble = i.id_inmueble
LEFT JOIN Imagenes_Inmuebles ii 
    ON ii.id_inmueble = i.id_inmueble
LEFT JOIN imagenes img 
    ON img.id_imagen = ii.id_imagen
WHERE 1=1
AND i.id_inmueble NOT IN (
    SELECT DISTINCT a2.id_inmueble
    FROM Contratos_Clientes cc
    JOIN Contratos co 
        ON cc.id_contrato = co.id_contrato
    JOIN Anuncios a2 
        ON a2.id_cliente = cc.id_vendedor
)
";

$params = [];

/* ===============================
   AÑADIR FILTROS
   =============================== */

// Tipo operación
if ($tipoOperacion !== '') {
    $sql .= " AND a.tipo = ? ";
    $params[] = $tipoOperacion;
}

// Tipo inmueble
if ($tipoInmueble !== '') {
    $sql .= " AND ti.subcategoria = ? ";
    $params[] = $tipoInmueble;
}

// Comunidad
if ($comunidad !== '') {
    $sql .= " AND c.id_comunidad = ? ";
    $params[] = $comunidad;
}

// Provincia
if ($provincia !== '') {
    $sql .= " AND p.id_provincia = ? ";
    $params[] = $provincia;
}

/* ===============================
   ORDEN Y LÍMITE
   =============================== */
$sql .= " ORDER BY i.id_inmueble DESC";

if (
    $tipoOperacion === '' &&
    $tipoInmueble === '' &&
    $comunidad === '' &&
    $provincia === ''
) {
    $sql .= " LIMIT 6";
}

/* ===============================
   EJECUTAR CONSULTA
   =============================== */
$inmuebles = consulta(
    $conexion,
    $sql,
    ['id_inmueble', 'm2', 'direccion', 'precio', 'tipo', 'provincia', 'comunidad', 'imagen'],
    $params
);
?>
<!DOCTYPE html>
<html>

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>

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

<body>
    <main class="container">

        <center>
            <h2>Bienvenido a nuestra Inmobiliaria</h2>
        </center>

        <!-- FORMULARIO FILTROS -->
        <section class="filters-section">
            <center>
                <h4>Busca donde hay inmuebles</h4>
            </center>

            <form method="POST" class="filtros">

                <div class="filter-group">
                    <label>Tipo de operación</label>
                    <select name="tipo_operacion">
                        <option value="">Todas</option>
                        <option value="venta" <?= $tipoOperacion == 'venta' ? 'selected' : '' ?>>Venta</option>
                        <option value="alquiler" <?= $tipoOperacion == 'alquiler' ? 'selected' : '' ?>>Alquiler</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Tipo de inmueble</label>
                    <select name="tipo_inmueble">
                        <option value="">Todas</option>
                        <?php foreach ($tipos as $tipo): ?>
                            <option value="<?= htmlspecialchars($tipo['subcategoria']) ?>"
                                <?= $tipoInmueble == $tipo['subcategoria'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tipo['subcategoria']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Comunidad Autónoma</label>
                    <select name="comunidad">
                        <option value="">Todas</option>
                        <?php foreach ($comunidades as $fila): ?>
                            <option value="<?= $fila['id_comunidad'] ?>"
                                <?= $comunidad == $fila['id_comunidad'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($fila['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Provincia</label>
                    <select name="provincia">
                        <option value="">Todas</option>
                        <?php foreach ($provincias as $fila): ?>
                            <option value="<?= $fila['id_provincia'] ?>"
                                <?= $provincia == $fila['id_provincia'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($fila['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <button type="submit">Buscar</button>
                </div>

            </form>
        </section>

        <!-- TARJETAS -->
        <center>
            <h4>Inmuebles</h4>
        </center>

        <div class="inmuebles">
            <?php if (count($inmuebles) === 0): ?>
                <p>No se encontraron inmuebles con esos filtros.</p>
            <?php endif; ?>

            <?php foreach ($inmuebles as $inmueble): ?>
                <div class="tarjeta-inmueble" data-id="<?= $inmueble['id_inmueble'] ?>">
                    <img src="../img/<?= htmlspecialchars($inmueble['imagen']) ?>">

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
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>
    <script src="../js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>