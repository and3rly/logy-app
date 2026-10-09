-- Traslados entre sucursales (enviar → recibir) y notificaciones de la campana.
-- Para una base que ya existe; en una nueva basta estructura.sql + /instalacion.
-- Se puede ejecutar de nuevo si una ejecución anterior quedó a medias: no repite lo que ya existe.
-- Origen <> destino lo valida la API: MySQL no admite un CHECK sobre columnas con llave foránea ON UPDATE CASCADE (error 3823).

-- Estados del traslado (ids usados en Inventario_traslado_model)
CREATE TABLE IF NOT EXISTS `inventario_traslado_estado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(45) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `etiqueta` varchar(20) DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_traslado_estado_empresa_codigo` (`empresa_id`,`codigo`),
  UNIQUE KEY `uq_traslado_estado_id_empresa` (`id`,`empresa_id`),
  CONSTRAINT `fk_traslado_estado_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- sucursal_id = origen (la que envía); sucursal_destino_id = la que recibe
CREATE TABLE IF NOT EXISTS `inventario_traslado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero` varchar(45) NOT NULL,
  `inventario_traslado_estado_id` int NOT NULL,
  `sucursal_destino_id` int NOT NULL,
  `observacion` varchar(300) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_enviado` datetime DEFAULT NULL,
  `fecha_recibido` datetime DEFAULT NULL,
  `fecha_anulado` datetime DEFAULT NULL,
  `anulado_motivo` varchar(500) DEFAULT NULL,
  `empresa_id` int NOT NULL,
  `sucursal_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `usuario_envio_id` int DEFAULT NULL,
  `usuario_recibio_id` int DEFAULT NULL,
  `usuario_anulo_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventario_traslado_empresa_numero` (`empresa_id`,`numero`),
  KEY `idx_inventario_traslado_estado_empresa` (`inventario_traslado_estado_id`,`empresa_id`),
  KEY `idx_inventario_traslado_sucursal` (`sucursal_id`),
  KEY `idx_inventario_traslado_sucursal_destino` (`sucursal_destino_id`),
  KEY `idx_inventario_traslado_usuario` (`usuario_id`),
  KEY `idx_inventario_traslado_usuario_envio` (`usuario_envio_id`),
  KEY `idx_inventario_traslado_usuario_recibio` (`usuario_recibio_id`),
  KEY `idx_inventario_traslado_usuario_anulo` (`usuario_anulo_id`),
  KEY `idx_inventario_traslado_fecha` (`fecha`),
  CONSTRAINT `fk_inventario_traslado_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_traslado_estado_empresa` FOREIGN KEY (`inventario_traslado_estado_id`, `empresa_id`) REFERENCES `inventario_traslado_estado` (`id`, `empresa_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_traslado_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_traslado_sucursal_destino` FOREIGN KEY (`sucursal_destino_id`) REFERENCES `sucursal` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_traslado_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_traslado_usuario_envio` FOREIGN KEY (`usuario_envio_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_traslado_usuario_recibio` FOREIGN KEY (`usuario_recibio_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_traslado_usuario_anulo` FOREIGN KEY (`usuario_anulo_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- fecha_vence: el lote del que sale (NULL = automático, el que vence primero)
CREATE TABLE IF NOT EXISTS `inventario_traslado_detalle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inventario_traslado_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `unidad_medida_id` int NOT NULL,
  `producto_presentacion_id` int DEFAULT NULL,
  `cantidad` decimal(15,2) NOT NULL,
  `costo` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `fecha_vence` datetime DEFAULT NULL,
  `observacion` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_traslado_detalle_traslado` (`inventario_traslado_id`),
  KEY `idx_traslado_detalle_producto` (`producto_id`),
  KEY `idx_traslado_detalle_unidad` (`unidad_medida_id`),
  KEY `idx_traslado_detalle_presentacion` (`producto_presentacion_id`),
  CONSTRAINT `fk_traslado_detalle_traslado` FOREIGN KEY (`inventario_traslado_id`) REFERENCES `inventario_traslado` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_traslado_detalle_presentacion` FOREIGN KEY (`producto_presentacion_id`) REFERENCES `producto_presentacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_traslado_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_traslado_detalle_unidad` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_traslado_detalle_cantidad` CHECK ((`cantidad` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Enlace del movimiento a la línea del traslado (solo si todavía no existe la columna)
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'movimiento' AND COLUMN_NAME = 'inventario_traslado_detalle_id');
SET @sql := IF(@existe = 0,
  'ALTER TABLE `movimiento`
    ADD COLUMN `inventario_traslado_detalle_id` int DEFAULT NULL AFTER `inventario_conversion_id`,
    ADD KEY `idx_movimiento_inventario_traslado_detalle` (`inventario_traslado_detalle_id`),
    ADD CONSTRAINT `fk_movimiento_inventario_traslado_detalle` FOREIGN KEY (`inventario_traslado_detalle_id`) REFERENCES `inventario_traslado_detalle` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1');
PREPARE agregar_columna FROM @sql;
EXECUTE agregar_columna;
DEALLOCATE PREPARE agregar_columna;

-- Notificaciones de la campana: para una sucursal (usuario_id NULL = todos sus usuarios) o para un usuario.
-- activo = 0 la retira para todos (ej. el traslado ya se recibió); la lectura es por usuario.
CREATE TABLE IF NOT EXISTS `notificacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` enum('aviso','info','exito') NOT NULL DEFAULT 'info',
  `icono` varchar(50) NOT NULL,
  `texto` varchar(300) NOT NULL,
  `ruta` varchar(100) DEFAULT NULL,
  `documento_id` int DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `empresa_id` int NOT NULL,
  `sucursal_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `usuario_origen_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notificacion_sucursal_fecha` (`sucursal_id`,`fecha`),
  KEY `idx_notificacion_documento` (`ruta`,`documento_id`),
  KEY `idx_notificacion_empresa` (`empresa_id`),
  KEY `idx_notificacion_usuario` (`usuario_id`),
  KEY `idx_notificacion_usuario_origen` (`usuario_origen_id`),
  CONSTRAINT `fk_notificacion_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_notificacion_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_notificacion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_notificacion_usuario_origen` FOREIGN KEY (`usuario_origen_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `notificacion_leida` (
  `notificacion_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notificacion_id`,`usuario_id`),
  KEY `idx_notificacion_leida_usuario` (`usuario_id`),
  CONSTRAINT `fk_notificacion_leida_notificacion` FOREIGN KEY (`notificacion_id`) REFERENCES `notificacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_notificacion_leida_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Estados del traslado en cada empresa (ids fijos, como los del ajuste)
INSERT INTO `inventario_traslado_estado` (`id`, `codigo`, `nombre`, `etiqueta`, `orden`, `activo`, `empresa_id`)
SELECT 1, 'BORRADOR', 'Borrador', 'primary', 10, 1, e.id FROM `empresa` e
WHERE NOT EXISTS (SELECT 1 FROM `inventario_traslado_estado` t WHERE t.empresa_id = e.id AND t.codigo = 'BORRADOR');

INSERT INTO `inventario_traslado_estado` (`id`, `codigo`, `nombre`, `etiqueta`, `orden`, `activo`, `empresa_id`)
SELECT 2, 'ENVIADO', 'Enviado', 'warning', 20, 1, e.id FROM `empresa` e
WHERE NOT EXISTS (SELECT 1 FROM `inventario_traslado_estado` t WHERE t.empresa_id = e.id AND t.codigo = 'ENVIADO');

INSERT INTO `inventario_traslado_estado` (`id`, `codigo`, `nombre`, `etiqueta`, `orden`, `activo`, `empresa_id`)
SELECT 3, 'RECIBIDO', 'Recibido', 'lime', 30, 1, e.id FROM `empresa` e
WHERE NOT EXISTS (SELECT 1 FROM `inventario_traslado_estado` t WHERE t.empresa_id = e.id AND t.codigo = 'RECIBIDO');

INSERT INTO `inventario_traslado_estado` (`id`, `codigo`, `nombre`, `etiqueta`, `orden`, `activo`, `empresa_id`)
SELECT 4, 'ANULADO', 'Anulado', 'danger', 40, 1, e.id FROM `empresa` e
WHERE NOT EXISTS (SELECT 1 FROM `inventario_traslado_estado` t WHERE t.empresa_id = e.id AND t.codigo = 'ANULADO');

-- Tipos de movimiento del traslado en cada empresa
INSERT INTO `movimiento_tipo` (`codigo`, `nombre`, `sentido`, `activo`, `empresa_id`)
SELECT 'TRS', 'Traslado salida', 'SALIDA', 1, e.id
FROM `empresa` e
WHERE NOT EXISTS (SELECT 1 FROM `movimiento_tipo` t WHERE t.empresa_id = e.id AND t.codigo = 'TRS');

INSERT INTO `movimiento_tipo` (`codigo`, `nombre`, `sentido`, `activo`, `empresa_id`)
SELECT 'TRE', 'Traslado entrada', 'ENTRADA', 1, e.id
FROM `empresa` e
WHERE NOT EXISTS (SELECT 1 FROM `movimiento_tipo` t WHERE t.empresa_id = e.id AND t.codigo = 'TRE');

INSERT INTO `movimiento_tipo` (`codigo`, `nombre`, `sentido`, `activo`, `empresa_id`)
SELECT 'ATS', 'Anulación de traslado salida', 'ENTRADA', 1, e.id
FROM `empresa` e
WHERE NOT EXISTS (SELECT 1 FROM `movimiento_tipo` t WHERE t.empresa_id = e.id AND t.codigo = 'ATS');

-- Opción del menú en Inventario. Se busca por url y el id lo asigna la base: si el 23 ya lo ocupa
-- otra opción creada a mano, igual se agrega (el código no depende del id del menú)
INSERT INTO `menu` (`modulo_id`, `nombre`, `orden`, `icono`, `url`, `activo`)
SELECT 8, 'Traslados', 6, 'fa-regular fa-circle', '/traslado', 1
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `url` = '/traslado');
