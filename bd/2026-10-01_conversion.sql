-- Conversión de presentaciones (fase 3): explosión (presentación → unidades) e implosión (unidades → presentación).
-- Para una base que ya existe; en una nueva basta estructura.sql + /instalacion.

CREATE TABLE `inventario_conversion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero` varchar(45) NOT NULL,
  `sentido` enum('EXPLOSION','IMPLOSION') NOT NULL,
  `producto_id` int NOT NULL,
  `unidad_medida_id` int NOT NULL,
  `producto_presentacion_id` int NOT NULL,
  `factor` decimal(10,5) NOT NULL,
  `cantidad` int NOT NULL,
  `unidades` decimal(15,2) NOT NULL,
  `costo` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `observacion` varchar(300) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `empresa_id` int NOT NULL,
  `sucursal_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventario_conversion_empresa_numero` (`empresa_id`,`numero`),
  KEY `idx_inventario_conversion_producto` (`producto_id`),
  KEY `idx_inventario_conversion_unidad_medida` (`unidad_medida_id`),
  KEY `idx_inventario_conversion_presentacion` (`producto_presentacion_id`),
  KEY `idx_inventario_conversion_sucursal` (`sucursal_id`),
  KEY `idx_inventario_conversion_usuario` (`usuario_id`),
  KEY `idx_inventario_conversion_fecha` (`fecha`),
  CONSTRAINT `fk_inventario_conversion_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_conversion_presentacion` FOREIGN KEY (`producto_presentacion_id`) REFERENCES `producto_presentacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_conversion_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_conversion_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_conversion_unidad_medida` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_conversion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

ALTER TABLE `movimiento`
  ADD COLUMN `inventario_conversion_id` int DEFAULT NULL AFTER `venta_detalle_id`,
  ADD KEY `idx_movimiento_inventario_conversion` (`inventario_conversion_id`),
  ADD CONSTRAINT `fk_movimiento_inventario_conversion` FOREIGN KEY (`inventario_conversion_id`) REFERENCES `inventario_conversion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Tipos de movimiento de la conversión en cada empresa
INSERT INTO `movimiento_tipo` (`codigo`, `nombre`, `sentido`, `activo`, `empresa_id`)
SELECT 'CVS', 'Conversión salida', 'SALIDA', 1, e.id
FROM `empresa` e
WHERE NOT EXISTS (SELECT 1 FROM `movimiento_tipo` t WHERE t.empresa_id = e.id AND t.codigo = 'CVS');

INSERT INTO `movimiento_tipo` (`codigo`, `nombre`, `sentido`, `activo`, `empresa_id`)
SELECT 'CVE', 'Conversión entrada', 'ENTRADA', 1, e.id
FROM `empresa` e
WHERE NOT EXISTS (SELECT 1 FROM `movimiento_tipo` t WHERE t.empresa_id = e.id AND t.codigo = 'CVE');

-- Opción del menú en Inventario
INSERT INTO `menu` (`id`, `modulo_id`, `nombre`, `orden`, `icono`, `url`, `activo`)
SELECT 20, 8, 'Conversiones', 5, 'fa-regular fa-circle', '/conversion', 1
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `id` = 20);
