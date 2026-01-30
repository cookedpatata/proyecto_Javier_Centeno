<?php
/* ===============================
   EXPRESIONES REGULARES
   =============================== */

$regexNombre     = '/^[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+$/u';
$regexApellidos  = '/^[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(\s[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)*$/u';
$regexDNI        = '/^[0-9]{8}[A-Z]$/';
$regexTel        = '/^[0-9]{9}$/';
$regexCorreo     = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
$regexPass       = '/^(?=.*[A-Z])(?=.*\d).{8,}$/';

/* ===============================
   VALIDACIONES DE PERSONA
   =============================== */

function validarNombre($nombre)
{
    global $regexNombre;
    return preg_match($regexNombre, $nombre);
}

function validarApellidos($apellidos)
{
    global $regexApellidos;
    return preg_match($regexApellidos, $apellidos);
}

function validarDNI($dni)
{
    global $regexDNI;
    return preg_match($regexDNI, strtoupper($dni));
}

function validarTelefono($tel)
{
    global $regexTel;
    return preg_match($regexTel, $tel);
}

function validarCorreo($correo)
{
    global $regexCorreo;
    return preg_match($regexCorreo, $correo);
}

function validarPassword($pass)
{
    global $regexPass;
    return preg_match($regexPass, $pass);
}

/* ===============================
   VALIDACIONES DE INMUEBLES
   =============================== */

function validarDireccion($direccion)
{
    return strlen(trim($direccion)) >= 5;
}

function validarPrecio($precio)
{
    return is_numeric($precio) && $precio > 0;
}

function validarMetros($m2)
{
    return is_numeric($m2) && $m2 > 0;
}

function validarId($id)
{
    return is_numeric($id) && $id > 0;
}

/* ===============================
   VALIDACIÓN COMPLETA CLIENTE
   =============================== */

function validarCliente($data)
{
    return
        validarNombre($data['nom']) &&
        validarApellidos($data['apellidos']) &&
        validarDNI($data['dni']) &&
        validarCorreo($data['correo']) &&
        validarPassword($data['password']);
}

/* ===============================
   VALIDACIÓN COMPLETA INMUEBLE
   =============================== */

function validarInmueble($data)
{
    return
        validarDireccion($data['direccion']) &&
        validarPrecio($data['precio']) &&
        validarMetros($data['m2']) &&
        validarId($data['id_provincia']) &&
        validarId($data['id_tipo']);
}
