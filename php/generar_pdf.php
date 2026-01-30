<?php
session_start();
require('../fpdf186/fpdf.php');
include("conexion.php");

/* ===============================
   PROTECCIÓN TRABAJADOR
   =============================== */
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'trabajador') {
    die("Acceso restringido");
}

$id_trabajador = $_SESSION['usuario_id'];

/* ===============================
   DATOS DEL TRABAJADOR
   =============================== */
$trabajador = consulta(
    $conexion,
    "
    SELECT nom, apellidos, correo
    FROM Trabajadores
    WHERE id_trabajador = ?
    ",
    ['nom', 'apellidos', 'correo'],
    [$id_trabajador]
)[0];

/* ===============================
   CONTRATOS + IMAGEN
   =============================== */
$contratos = consulta(
    $conexion,
    "
    SELECT
        co.id_contrato,
        co.fecha,
        i.direccion,
        i.precio,
        img.ruta AS imagen,
        c1.nom AS comprador,
        c2.nom AS vendedor
    FROM Contratos co
    JOIN Inmuebles i ON co.id_inmueble = i.id_inmueble
    JOIN Contratos_Clientes cc ON cc.id_contrato = co.id_contrato
    JOIN Clientes c1 ON cc.id_cliente = c1.id_cliente
    JOIN Clientes c2 ON cc.id_vendedor = c2.id_cliente
    LEFT JOIN Imagenes_Inmuebles ii ON ii.id_inmueble = i.id_inmueble
    LEFT JOIN imagenes img ON img.id_imagen = ii.id_imagen
    WHERE co.id_trabajador = ?
    GROUP BY co.id_contrato
    ORDER BY co.fecha DESC
    ",
    [
        'id_contrato',
        'fecha',
        'direccion',
        'precio',
        'imagen',
        'comprador',
        'vendedor'
    ],
    [$id_trabajador]
);

/* ===============================
   CREAR PDF
   =============================== */
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

/* TÍTULO */
$pdf->Cell(0, 10, utf8_decode('Informe de contratos'), 0, 1, 'C');
$pdf->Ln(4);

/* DATOS TRABAJADOR */
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, utf8_decode("Trabajador: {$trabajador['nom']} {$trabajador['apellidos']}"), 0, 1);
$pdf->Cell(0, 8, utf8_decode("Correo: {$trabajador['correo']}"), 0, 1);
$pdf->Ln(5);

/* ===============================
   CONTRATOS
   =============================== */
$pdf->SetFont('Arial', 'B', 11);

if (count($contratos) === 0) {
    $pdf->Cell(0, 10, utf8_decode('No hay contratos asignados'), 1, 1);
} else {

    foreach ($contratos as $c) {

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, "Contrato #{$c['id_contrato']}  -  {$c['fecha']}", 0, 1);
        $pdf->Ln(2);

        /* IMAGEN */
        $rutaImagen = "../img/" . ($c['imagen'] ?: 'inmueble_default.jpg');

        if (file_exists($rutaImagen)) {
            $pdf->Image($rutaImagen, $pdf->GetX(), $pdf->GetY(), 60);
            $pdf->Ln(45);
        } else {
            $pdf->Ln(5);
        }

        /* DATOS */
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, utf8_decode("Dirección: {$c['direccion']}"), 0, 1);
        $pdf->Cell(0, 6, "Precio: " . number_format($c['precio'], 0, ',', '.') . " €", 0, 1);
        $pdf->Cell(0, 6, utf8_decode("Comprador: {$c['comprador']}"), 0, 1);
        $pdf->Cell(0, 6, utf8_decode("Vendedor: {$c['vendedor']}"), 0, 1);

        $pdf->Ln(6);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(6);
    }
}

/* ===============================
   SALIDA
   =============================== */
$pdf->Output('I', 'contratos_trabajador.pdf');
