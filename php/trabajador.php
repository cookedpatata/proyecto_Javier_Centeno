<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die('Acceso no autorizado');
}

$id_trabajador = (int) $_SESSION['usuario_id'];

$sql = "
SELECT
    c.id_contrato,
    c.fecha,

    i.direccion,
    i.m2,
    i.precio,

    ti.subcategoria AS tipo_inmueble,

    comp.nom AS comprador_nombre,
    vend.nom AS vendedor_nombre

FROM Contratos c
INNER JOIN Inmuebles i ON c.id_inmueble = i.id_inmueble
INNER JOIN tipos_inmuebles ti ON i.id_tipo = ti.id_tipo
INNER JOIN Contratos_Clientes cc ON c.id_contrato = cc.id_contrato
INNER JOIN Clientes comp ON cc.id_cliente = comp.id_cliente
INNER JOIN Clientes vend ON cc.id_vendedor = vend.id_cliente

WHERE c.id_trabajador = :id_trabajador
ORDER BY c.fecha DESC
";

$stmt = $conexion->prepare($sql);
$stmt->bindParam(':id_trabajador', $id_trabajador, PDO::PARAM_INT);
$stmt->execute();

$contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Contratos del trabajador</title>
    <link rel="stylesheet" href="../styles.css">
</head>

<body>

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

        <main>
            <h2>Contratos asignados</h2>

            <?php if (empty($contratos)): ?>
                <p>No hay contratos asociados a este trabajador.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Inmueble</th>
                            <th>Tipo</th>
                            <th>Precio</th>
                            <th>Comprador</th>
                            <th>Vendedor</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contratos as $c): ?>
                            <tr>
                                <td><?= $c['id_contrato'] ?></td>
                                <td><?= $c['fecha'] ?></td>
                                <td><?= $c['direccion'] ?> (<?= $c['m2'] ?> m²)</td>
                                <td><?= $c['tipo_inmueble'] ?></td>
                                <td><?= number_format($c['precio'], 2) ?> €</td>
                                <td><?= $c['comprador_nombre'] ?></td>
                                <td><?= $c['vendedor_nombre'] ?></td>
                                <td>
                                    <form action="generar_pdf.php" method="get" target="_blank">
                                        <input type="hidden" name="id_contrato" value="<?= $c['id_contrato'] ?>">
                                        <button type="submit">Procesar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>

    </body>

</html>