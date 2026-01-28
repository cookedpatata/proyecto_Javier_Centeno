drop database Inmobiliaria;

Create database if not exists Inmobiliaria;

use Inmobiliaria;

CREATE TABLE if not exists Clientes (
    id_cliente INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    DNI VARCHAR(15) UNIQUE NOT NULL,
    tel VARCHAR(15),
    correo VARCHAR(100) UNIQUE NOT NULL,
    contraseña VARCHAR(100) NOT NULL
)
ENGINE =INNODB
;

CREATE TABLE if not exists Trabajadores (
    id_trabajador INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    DNI VARCHAR(15) UNIQUE NOT NULL,
    tel VARCHAR(15),
    correo VARCHAR(100) UNIQUE NOT NULL,
    contraseña VARCHAR(100) NOT NULL
)
ENGINE =INNODB
;

CREATE TABLE IF NOT EXISTS comunidades (
    id_comunidad INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
)
ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS provincias (
    id_provincia INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    id_comunidad INT NOT NULL,
    FOREIGN KEY (id_comunidad) 
        REFERENCES comunidades(id_comunidad)
        ON DELETE CASCADE
)
ENGINE=INNODB;


CREATE TABLE IF NOT EXISTS imagenes (
    id_imagen INT AUTO_INCREMENT PRIMARY KEY,
    ruta VARCHAR(100) NOT NULL
)
ENGINE =INNODB
;

CREATE TABLE if not exists tipos_inmuebles (
    id_tipo INT PRIMARY KEY AUTO_INCREMENT,
    categoria VARCHAR(50) NOT NULL, 
    subcategoria VARCHAR(50)        
)
ENGINE =INNODB
;

CREATE TABLE IF NOT EXISTS Inmuebles (
    id_inmueble INT PRIMARY KEY AUTO_INCREMENT,
    m2 DECIMAL(10,2),
    direccion VARCHAR(150),
    precio DECIMAL(12,2),
    id_cliente INT,
    id_provincia INT NOT NULL,
    id_tipo INT,

    FOREIGN KEY (id_tipo) REFERENCES tipos_inmuebles(id_tipo) ON DELETE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente) ON DELETE CASCADE,
    FOREIGN KEY (id_provincia) REFERENCES provincias(id_provincia)
)
ENGINE=INNODB;


CREATE TABLE IF NOT EXISTS Anuncios (
    id_anuncio INT PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('alquiler', 'venta') NOT NULL,
    id_cliente INT,
    id_inmueble INT,
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente) ON DELETE CASCADE,
    FOREIGN KEY (id_inmueble) REFERENCES Inmuebles(id_inmueble)
)
ENGINE =INNODB
;

CREATE TABLE if not exists Contratos (
    id_contrato INT PRIMARY KEY AUTO_INCREMENT,
    id_inmueble INT,
    fecha DATE NOT NULL,
    id_trabajador INT,
    FOREIGN KEY (id_inmueble) REFERENCES inmuebles(id_inmueble),
    FOREIGN KEY (id_trabajador) REFERENCES Trabajadores(id_trabajador)
)
ENGINE =INNODB
;

CREATE TABLE if not exists Contratos_Clientes (
    id_contrato INT,
    id_cliente INT,
    id_vendedor INT,
    PRIMARY KEY (id_contrato),
    FOREIGN KEY (id_contrato) REFERENCES Contratos(id_contrato),
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente),
    FOREIGN KEY (id_vendedor) REFERENCES Clientes(id_cliente)
)
ENGINE =INNODB
;

CREATE TABLE if not exists Imagenes_Inmuebles (
    id_imagen INT,
    id_inmueble INT,
    FOREIGN KEY (id_imagen) REFERENCES imagenes(id_imagen),
    FOREIGN KEY (id_inmueble) REFERENCES Inmuebles(id_inmueble)
)
ENGINE =INNODB
;