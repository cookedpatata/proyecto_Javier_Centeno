<?php
require_once 'conexion.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;

if (!isset($_GET['id_contrato'])) {
    die('Contrato no especificado');
}

$id_contrato = (int) $_GET['id_contrato'];

$sql = "
SELECT
    c.id_contrato,
    c.fecha,

    i.direccion,
    i.m2,
    i.precio,

    ti.subcategoria AS tipo_inmueble,

    comp.nom AS comprador_nombre,
    comp.apellidos AS comprador_apellidos,
    comp.correo AS comprador_correo,
    comp.tel AS comprador_tel,

    vend.nom AS vendedor_nombre,
    vend.apellidos AS vendedor_apellidos,
    vend.correo AS vendedor_correo,
    vend.tel AS vendedor_tel

FROM Contratos c
INNER JOIN Inmuebles i ON c.id_inmueble = i.id_inmueble
INNER JOIN tipos_inmuebles ti ON i.id_tipo = ti.id_tipo
INNER JOIN Contratos_Clientes cc ON c.id_contrato = cc.id_contrato
INNER JOIN Clientes comp ON cc.id_cliente = comp.id_cliente
INNER JOIN Clientes vend ON cc.id_vendedor = vend.id_cliente

WHERE c.id_contrato = :id_contrato
";

$stmt = $conexion->prepare($sql);
$stmt->bindParam(':id_contrato', $id_contrato, PDO::PARAM_INT);
$stmt->execute();

$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$datos) {
    die('Contrato no encontrado');
}

$html = "
<style>
body { font-family: Arial, sans-serif; }
h1 { text-align: center; }
.section { margin-bottom: 20px; }
</style>

<h1>Contrato nº {$datos['id_contrato']}</h1>

<div class='section'>
<h3>Datos del inmueble</h3>
Dirección: {$datos['direccion']}<br>
Metros: {$datos['m2']} m²<br>
Tipo: {$datos['tipo_inmueble']}<br>
Precio: {$datos['precio']} €
</div>

<div class='section'>
<h3>Comprador</h3>
{$datos['comprador_nombre']} {$datos['comprador_apellidos']}<br>
{$datos['comprador_correo']}<br>
{$datos['comprador_tel']}
</div>

<div class='section'>
<h3>Vendedor</h3>
{$datos['vendedor_nombre']} {$datos['vendedor_apellidos']}<br>
{$datos['vendedor_correo']}<br>
{$datos['vendedor_tel']}
</div>

<div class='section'>
<h3>Fecha del contrato</h3>
{$datos['fecha']}
</div>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream(
    "contrato_{$datos['id_contrato']}.pdf",
    ["Attachment" => false]
);
