INSERT INTO tipos_inmuebles (categoria, subcategoria) VALUES
('Piso', 'Apartamento'),
('Piso', 'Dúplex'),
('Piso', 'Piso'),
('Piso', 'Ático'),
('Casa', 'Casa'),
('Casa', 'Adosada'),
('Casa', 'Rústica'),
('Casa', 'Chalé rústico');

INSERT INTO comunidades (id_comunidad, nombre) VALUES
(1, 'Andalucía'),
(2, 'Aragón'),
(3, 'Asturias'),
(4, 'Islas Baleares'),
(5, 'Canarias'),
(6, 'Cantabria'),
(7, 'Castilla-La Mancha'),
(8, 'Castilla y León'),
(9, 'Cataluña'),
(10, 'Comunidad Valenciana'),
(11, 'Extremadura'),
(12, 'Galicia'),
(13, 'La Rioja'),
(14, 'Comunidad de Madrid'),
(15, 'Región de Murcia'),
(16, 'Navarra'),
(17, 'País Vasco'),
(18, 'Ceuta'),
(19, 'Melilla');

INSERT INTO provincias (nombre, id_comunidad) VALUES
-- Andalucía (1)
('Almería', 1),
('Cádiz', 1),
('Córdoba', 1),
('Granada', 1),
('Huelva', 1),
('Jaén', 1),
('Málaga', 1),
('Sevilla', 1),

-- Aragón (2)
('Huesca', 2),
('Teruel', 2),
('Zaragoza', 2),

-- Asturias (3)
('Asturias', 3),

-- Islas Baleares (4)
('Islas Baleares', 4),

-- Canarias (5)
('Las Palmas', 5),
('Santa Cruz de Tenerife', 5),

-- Cantabria (6)
('Cantabria', 6),

-- Castilla-La Mancha (7)
('Albacete', 7),
('Ciudad Real', 7),
('Cuenca', 7),
('Guadalajara', 7),
('Toledo', 7),

-- Castilla y León (8)
('Ávila', 8),
('Burgos', 8),
('León', 8),
('Palencia', 8),
('Salamanca', 8),
('Segovia', 8),
('Soria', 8),
('Valladolid', 8),
('Zamora', 8),

-- Cataluña (9)
('Barcelona', 9),
('Girona', 9),
('Lleida', 9),
('Tarragona', 9),

-- Comunidad Valenciana (10)
('Alicante', 10),
('Castellón', 10),
('Valencia', 10),

-- Extremadura (11)
('Badajoz', 11),
('Cáceres', 11),

-- Galicia (12)
('A Coruña', 12),
('Lugo', 12),
('Ourense', 12),
('Pontevedra', 12),

-- La Rioja (13)
('La Rioja', 13),

-- Comunidad de Madrid (14)
('Madrid', 14),

-- Región de Murcia (15)
('Murcia', 15),

-- Navarra (16)
('Navarra', 16),

-- País Vasco (17)
('Álava', 17),
('Bizkaia', 17),
('Gipuzkoa', 17),

-- Ceuta (18)
('Ceuta', 18),

-- Melilla (19)
('Melilla', 19);

-- Clientes (contraseñas MD5)
INSERT INTO Clientes (nom, apellidos, DNI, tel, correo, contraseña) VALUES
('Juan', 'Pérez Gómez', '12345678A', '600111222', 'juan@gmail.com', MD5('juan123')),
('María', 'López Ruiz', '23456789B', '600333444', 'maria@gmail.com', MD5('maria123')),
('Carlos', 'Sánchez Díaz', '34567890C', '600555666', 'carlos@gmail.com', MD5('carlos123')),
('Ana', 'García López', '45678912F', '600777888', 'ana@gmail.com', MD5('ana123')),
('Luis', 'Fernández Martín', '56789123G', '600999111', 'luis@gmail.com', MD5('luis123')),
('Elena', 'Ruiz Morales', '67891234H', '601222333', 'elena@gmail.com', MD5('elena123')),
('Javier', 'Ortega Sánchez', '78912345J', '601444555', 'javier@gmail.com', MD5('javier123')),
('Marta', 'Navarro Gil', '89123456K', '601666777', 'marta@gmail.com', MD5('marta123'));

-- Trabajadores
INSERT INTO Trabajadores (nom, apellidos, DNI, tel, correo, contraseña) VALUES
('Laura', 'Martín Torres', '45678901D', '611111111', 'laura@empresa.com', MD5('laura123')),
('Pedro', 'Gómez Navarro', '56789012E', '622222222', 'pedro@empresa.com', MD5('pedro123')),
('Ana', 'García López', '45678912F', '600777888', 'ana@gmail.com', MD5('ana123')),
('Luis', 'Fernández Martín', '56789123G', '600999111', 'luis@gmail.com', MD5('luis123')),
('Elena', 'Ruiz Morales', '67891234H', '601222333', 'elena@gmail.com', MD5('elena123')),
('Javier', 'Ortega Sánchez', '78912345J', '601444555', 'javier@gmail.com', MD5('javier123')),
('Marta', 'Navarro Gil', '89123456K', '601666777', 'marta@gmail.com', MD5('marta123'));

-- Inmuebles
INSERT INTO Inmuebles (m2, direccion, precio, id_cliente, id_provincia, id_tipo) VALUES
(80.50, 'Calle Mayor 10', 180000.00, 1, 1, 1),
(120.00, 'Avenida Europa 25', 250000.00, 2, 3, 2),
(60.00, 'Calle Comercio 5', 90000.00, 3, 5, 3),
(95.00, 'Calle Sol 12', 210000.00, 4, 6, 4),
(70.00, 'Plaza España 3', 145000.00, 5, 7, 1),
(150.00, 'Camino Real 45', 320000.00, 6, 10, 6),
(55.00, 'Calle Luna 8', 98000.00, 7, 11, 3),
(200.00, 'Urbanización Los Pinos 2', 450000.00, 8, 12, 7);

-- Anuncios
INSERT INTO Anuncios (tipo, id_cliente, id_inmueble) VALUES
('venta', 1, 1),
('alquiler', 2, 2),
('venta', 3, 3),
('venta', 4, 4),
('alquiler', 5, 5),
('venta', 6, 6),
('alquiler', 7, 7),
('venta', 8, 8);

INSERT INTO imagenes (ruta) VALUES
('1.jpeg'), ('2.jpeg'), ('3.jpeg'),
('4.jpeg'), ('5.jpeg'), ('6.jpg'),
('7.jpg'), ('8.jpg');

INSERT INTO Imagenes_Inmuebles (id_inmueble, id_imagen) VALUES
(1, 1), 
(2, 2),
(3, 3), 
(4, 4),
(5, 5),
(6, 6),
(7, 7),
(8, 8);