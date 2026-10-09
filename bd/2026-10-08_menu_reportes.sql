-- Módulo Reportes y su opción "Ventas por día" (/ventas-dia) para las bases que no los tienen.
-- Se puede ejecutar más de una vez: solo inserta lo que falta.

-- Módulo Reportes (id 10, como en config/instalacion.php)
INSERT INTO `modulo` (`id`, `nombre`, `icono`, `url`, `orden`, `detalle`, `activo`)
SELECT 10, 'Reportes', 'fa-solid fa-chart-column', NULL, 7, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `modulo` WHERE `id` = 10);

-- Opción del menú. Se busca por url y el id lo asigna la base si el 21 ya está ocupado
-- (el código no depende del id del menú)
INSERT INTO `menu` (`id`, `modulo_id`, `nombre`, `orden`, `icono`, `url`, `activo`)
SELECT IF(EXISTS (SELECT 1 FROM `menu` WHERE `id` = 21), NULL, 21), 10, 'Ventas por día', 1, 'fa-regular fa-circle', '/ventas-dia', 1
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `url` = '/ventas-dia');
