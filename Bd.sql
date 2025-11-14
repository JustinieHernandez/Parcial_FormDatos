-- Base de Datos: db_parcial_formdatos

-- 1. CREACIÓN DE LA BASE DE DATOS
-- Cree la base de datos en phpMyAdmin
CREATE DATABASE IF NOT EXISTS db_parcial_formdatos;
USE parcial_db;


-- 2. CREACIÓN DE TABLA 'paises' (Datos de referencia estáticos)

CREATE TABLE IF NOT EXISTS paises (
    id_pais INT AUTO_INCREMENT PRIMARY KEY,
    nombre_pais VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS areas_interes (
    id_area INT AUTO_INCREMENT PRIMARY KEY,
    nombre_area VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. CREACIÓN DE TABLA PRINCIPAL 'inscriptores' (Datos del formulario)

-- Incluye la fecha del formulario (escogida por el usuario) y la fecha de registro (del sistema).
CREATE TABLE IF NOT EXISTS inscriptores (
    id_inscriptor INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    edad INT NOT NULL,
    sexo VARCHAR(20) NOT NULL,
    id_pais_residencia INT NOT NULL,
    nacionalidad VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    celular VARCHAR(20) NOT NULL UNIQUE,
    observaciones TEXT,
    fecha_formulario DATE NOT NULL COMMENT 'Fecha seleccionada por el usuario en el formulario.',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de inserción del registro en el sistema.',
    FOREIGN KEY (id_pais_residencia) REFERENCES paises(id_pais)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. CREACIÓN DE TABLA DE RELACIÓN 'inscriptor_areas' (Relación N:M)

CREATE TABLE IF NOT EXISTS inscriptor_areas (
    id_inscriptor INT NOT NULL,
    id_area INT NOT NULL,
    PRIMARY KEY (id_inscriptor, id_area),
    FOREIGN KEY (id_inscriptor) REFERENCES inscriptores(id_inscriptor) ON DELETE CASCADE,
    FOREIGN KEY (id_area) REFERENCES areas_interes(id_area) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 6. INSERCIÓN DE DATOS ESTÁTICOS EN 'paises'
-- Datos necesarios para que el <select> de País funcione.
INSERT INTO paises (nombre_pais) VALUES
('Panamá'),
('Costa Rica'),
('Colombia'),
('México'),
('Argentina'),
('España'),
('Estados Unidos');

-- 7. INSERCIÓN DE DATOS ESTÁTICOS EN 'areas_interes'
-- Datos necesarios para que los checkboxes de Temas Tecnológicos funcionen.
INSERT INTO areas_interes (nombre_area) VALUES
('Inteligencia Artificial'),
('Desarrollo Web (Front-end)'),
('Desarrollo Web (Back-end)'),
('Ciberseguridad'),
('Blockchain'),
('Computación en la Nube');
