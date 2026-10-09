-- Listas de precios (decisión 0025): precio fijo por producto o presentación, asignado al cliente.
-- Un producto que no está en la lista del cliente se vende al precio general (producto.precio × factor).
-- Para una base que ya existe (db_logy); en una nueva basta estructura.sql + /instalacion.

CREATE TABLE `lista_precio` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `empresa_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lista_precio_id_empresa` (`id`,`empresa_id`),
  KEY `idx_lista_precio_empresa` (`empresa_id`),
  KEY `idx_lista_precio_usuario` (`usuario_id`),
  CONSTRAINT `fk_lista_precio_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_lista_precio_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Sin presentación (NULL) el precio es de la unidad de medida del producto
CREATE TABLE `lista_precio_detalle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lista_precio_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `producto_presentacion_id` int DEFAULT NULL,
  `precio` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lista_precio_detalle` (`lista_precio_id`,`producto_id`,`producto_presentacion_id`),
  KEY `idx_lista_precio_detalle_producto` (`producto_id`),
  KEY `idx_lista_precio_detalle_presentacion` (`producto_presentacion_id`),
  KEY `idx_lista_precio_detalle_usuario` (`usuario_id`),
  CONSTRAINT `fk_lista_precio_detalle_lista` FOREIGN KEY (`lista_precio_id`) REFERENCES `lista_precio` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_lista_precio_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_lista_precio_detalle_presentacion` FOREIGN KEY (`producto_presentacion_id`) REFERENCES `producto_presentacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_lista_precio_detalle_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Lista del cliente (NULL = precio general)
ALTER TABLE `cliente`
  ADD COLUMN `lista_precio_id` int DEFAULT NULL AFTER `credito_dias`,
  ADD KEY `fk_cliente_lista_precio_empresa` (`lista_precio_id`,`empresa_id`),
  ADD CONSTRAINT `fk_cliente_lista_precio_empresa` FOREIGN KEY (`lista_precio_id`, `empresa_id`) REFERENCES `lista_precio` (`id`, `empresa_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Lista con la que se cotizó y con la que se vendió (para reportes)
ALTER TABLE `cotizacion`
  ADD COLUMN `lista_precio_id` int DEFAULT NULL AFTER `cliente_id`,
  ADD KEY `fk_cotizacion_lista_precio_empresa` (`lista_precio_id`,`empresa_id`),
  ADD CONSTRAINT `fk_cotizacion_lista_precio_empresa` FOREIGN KEY (`lista_precio_id`, `empresa_id`) REFERENCES `lista_precio` (`id`, `empresa_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `venta`
  ADD COLUMN `lista_precio_id` int DEFAULT NULL AFTER `cotizacion_id`,
  ADD KEY `fk_venta_lista_precio_empresa` (`lista_precio_id`,`empresa_id`),
  ADD CONSTRAINT `fk_venta_lista_precio_empresa` FOREIGN KEY (`lista_precio_id`, `empresa_id`) REFERENCES `lista_precio` (`id`, `empresa_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Opción del menú en Catálogos
INSERT INTO `menu` (`id`, `modulo_id`, `nombre`, `orden`, `icono`, `url`, `activo`)
SELECT 22, 1, 'Listas de precios', 7, 'fa-solid fa-tags', '/lista-precio', 1
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `id` = 22);
