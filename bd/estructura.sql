-- Estructura de db_logy (sin datos), generada el 2026-09-28 desde MySQL 8.
-- Crear la base vacía, ejecutar este script y luego abrir /instalacion en la interfaz.

CREATE DATABASE IF NOT EXISTS `db_logy` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci;
USE `db_logy`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- modulo
CREATE TABLE `modulo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `icono` varchar(50) DEFAULT NULL,
  `url` varchar(100) DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `detalle` tinyint(1) NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- pais
CREATE TABLE `pais` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- venta_estado
CREATE TABLE `venta_estado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(70) NOT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `etiqueta` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_venta_estado_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- departamento
CREATE TABLE `departamento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `pais_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_departamento_pais_idx` (`pais_id`),
  CONSTRAINT `fk_departamento_pais` FOREIGN KEY (`pais_id`) REFERENCES `pais` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- menu
CREATE TABLE `menu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `modulo_id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `icono` varchar(50) DEFAULT NULL,
  `url` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_menu_modulo1_idx` (`modulo_id`),
  CONSTRAINT `fk_menu_modulo1` FOREIGN KEY (`modulo_id`) REFERENCES `modulo` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- municipio
CREATE TABLE `municipio` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `departamento_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_municipio_departamento1_idx` (`departamento_id`),
  CONSTRAINT `fk_municipio_departamento1` FOREIGN KEY (`departamento_id`) REFERENCES `departamento` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- empresa
CREATE TABLE `empresa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(300) NOT NULL,
  `razon_social` varchar(300) NOT NULL,
  `identificacion` varchar(100) NOT NULL,
  `direccion` varchar(200) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `correo` varchar(250) DEFAULT NULL,
  `logo` varchar(350) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `municipio_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_empresa_municipio1_idx` (`municipio_id`),
  CONSTRAINT `fk_empresa_municipio1` FOREIGN KEY (`municipio_id`) REFERENCES `municipio` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- forma_pago
CREATE TABLE `forma_pago` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_forma_pago_empresa1_idx` (`empresa_id`),
  CONSTRAINT `fk_forma_pago_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- inventario_ajuste_estado
CREATE TABLE `inventario_ajuste_estado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(45) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `etiqueta` varchar(20) DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ajuste_estado_empresa_codigo` (`empresa_id`,`codigo`),
  UNIQUE KEY `uq_ajuste_estado_id_empresa` (`id`,`empresa_id`),
  CONSTRAINT `fk_ajuste_estado_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- inventario_estado
CREATE TABLE `inventario_estado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(45) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventario_estado_empresa_codigo` (`empresa_id`,`codigo`),
  UNIQUE KEY `uq_inventario_estado_id_empresa` (`id`,`empresa_id`),
  CONSTRAINT `fk_inventario_estado_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- inventario_tipo
CREATE TABLE `inventario_tipo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(45) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventario_tipo_empresa_codigo` (`empresa_id`,`codigo`),
  UNIQUE KEY `uq_inventario_tipo_id_empresa` (`id`,`empresa_id`),
  CONSTRAINT `fk_inventario_tipo_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- marca
CREATE TABLE `marca` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_marca_empresa1_idx` (`empresa_id`),
  CONSTRAINT `fk_marca_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- moneda
CREATE TABLE `moneda` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(45) DEFAULT NULL,
  `simbolo` varchar(5) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_moneda_empresa1_idx` (`empresa_id`),
  CONSTRAINT `fk_moneda_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- movimiento_tipo
CREATE TABLE `movimiento_tipo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(45) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `sentido` enum('ENTRADA','SALIDA') NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_movimiento_tipo_empresa_codigo` (`empresa_id`,`codigo`),
  UNIQUE KEY `uq_movimiento_tipo_id_empresa` (`id`,`empresa_id`),
  KEY `fk_movimiento_tipo_empresa1_idx` (`empresa_id`),
  CONSTRAINT `fk_movimiento_tipo_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- rol
CREATE TABLE `rol` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) NOT NULL,
  `administrador` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_rol_empresa1_idx` (`empresa_id`),
  CONSTRAINT `fk_rol_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- rol_acceso
CREATE TABLE `rol_acceso` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rol_id` int NOT NULL,
  `modulo_id` int NOT NULL,
  `menu_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rol_acceso` (`rol_id`,`modulo_id`,`menu_id`),
  KEY `fk_rol_acceso_modulo1_idx` (`modulo_id`),
  KEY `fk_rol_acceso_menu1_idx` (`menu_id`),
  CONSTRAINT `fk_rol_acceso_menu1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`),
  CONSTRAINT `fk_rol_acceso_modulo1` FOREIGN KEY (`modulo_id`) REFERENCES `modulo` (`id`),
  CONSTRAINT `fk_rol_acceso_rol1` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- unidad_medida
CREATE TABLE `unidad_medida` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_unidad_medida_empresa1_idx` (`empresa_id`),
  CONSTRAINT `fk_unidad_medida_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- usuario
CREATE TABLE `usuario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `alias` varchar(50) NOT NULL,
  `clave` varchar(500) NOT NULL,
  `correo` varchar(250) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `foto` varchar(350) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `empresa_id` int NOT NULL,
  `rol_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_usuario_empresa1_idx` (`empresa_id`),
  KEY `fk_usuario_rol1_idx` (`rol_id`),
  CONSTRAINT `fk_usuario_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`),
  CONSTRAINT `fk_usuario_rol1` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- venta_serie
CREATE TABLE `venta_serie` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nombre` varchar(70) NOT NULL,
  `codigo` varchar(5) NOT NULL,
  `inicio` int NOT NULL DEFAULT '1',
  `fin` int NOT NULL DEFAULT '999999999',
  `correlativo` int NOT NULL DEFAULT '0',
  `electronico` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_venta_serie_empresa_codigo` (`empresa_id`,`codigo`),
  UNIQUE KEY `uq_venta_serie_id_empresa` (`id`,`empresa_id`),
  KEY `idx_venta_serie_usuario` (`usuario_id`),
  CONSTRAINT `fk_venta_serie_ref_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_serie_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- categoria
CREATE TABLE `categoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `etiqueta` varchar(45) NOT NULL DEFAULT 'default',
  `empresa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_categoria_empresa1_idx` (`empresa_id`),
  CONSTRAINT `fk_categoria_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- cliente
CREATE TABLE `cliente` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nombre` varchar(150) NOT NULL,
  `razon_social` varchar(150) DEFAULT NULL,
  `identificacion` varchar(20) DEFAULT NULL,
  `codigo` varchar(10) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `telefono` int DEFAULT NULL,
  `correo` varchar(70) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `credito` tinyint(1) NOT NULL DEFAULT '0',
  `credito_limite` decimal(15,5) DEFAULT NULL,
  `credito_dias` int NOT NULL DEFAULT '0',
  `empresa_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `municipio_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cliente_id_empresa` (`id`,`empresa_id`),
  KEY `idx_cliente_empresa` (`empresa_id`),
  KEY `idx_cliente_usuario` (`usuario_id`),
  KEY `idx_cliente_municipio` (`municipio_id`),
  KEY `idx_cliente_identificacion` (`identificacion`),
  KEY `idx_cliente_codigo` (`codigo`),
  CONSTRAINT `fk_cliente_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cliente_municipio` FOREIGN KEY (`municipio_id`) REFERENCES `municipio` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cliente_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- compra_estado
CREATE TABLE `compra_estado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  `etiqueta` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_compra_estado_empresa1_idx` (`empresa_id`),
  CONSTRAINT `fk_compra_estado_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- cotizacion_estado
CREATE TABLE `cotizacion_estado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(30) NOT NULL,
  `nombre` varchar(70) NOT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `etiqueta` varchar(100) DEFAULT NULL,
  `empresa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cotizacion_estado_empresa_codigo` (`empresa_id`,`codigo`),
  UNIQUE KEY `uq_cotizacion_estado_id_empresa` (`id`,`empresa_id`),
  CONSTRAINT `fk_cotizacion_ref_estado_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- cotizacion_serie
CREATE TABLE `cotizacion_serie` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nombre` varchar(70) NOT NULL,
  `codigo` varchar(5) NOT NULL,
  `inicio` int NOT NULL DEFAULT '1',
  `fin` int NOT NULL DEFAULT '999999999',
  `correlativo` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cotizacion_serie_empresa_codigo` (`empresa_id`,`codigo`),
  UNIQUE KEY `uq_cotizacion_serie_id_empresa` (`id`,`empresa_id`),
  KEY `idx_cotizacion_serie_usuario` (`usuario_id`),
  CONSTRAINT `fk_cotizacion_ref_serie_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cotizacion_serie_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- cuenta_cobrar
CREATE TABLE `cuenta_cobrar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cliente_id` int NOT NULL,
  `factura_fecha` date NOT NULL,
  `factura_numero` varchar(45) NOT NULL,
  `factura_documento` varchar(100) DEFAULT NULL,
  `credito_dias` int NOT NULL DEFAULT '0',
  `fecha_vence` date NOT NULL,
  `total` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `abono` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `saldo` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `moneda_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `venta_id` int DEFAULT NULL,
  `referencia` varchar(300) DEFAULT NULL,
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  `anulado_fecha` datetime DEFAULT NULL,
  `anulado_motivo` varchar(300) DEFAULT NULL,
  `anulado_usuario` int DEFAULT NULL,
  `origen` int NOT NULL DEFAULT '1' COMMENT '1=Manual, 2=Venta',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cuenta_cobrar_empresa_venta` (`empresa_id`,`venta_id`),
  KEY `idx_cuenta_cobrar_cliente` (`cliente_id`),
  KEY `idx_cuenta_cobrar_empresa_estado` (`empresa_id`,`anulado`,`saldo`),
  KEY `idx_cuenta_cobrar_vencimiento` (`empresa_id`,`fecha_vence`),
  KEY `idx_cuenta_cobrar_moneda` (`moneda_id`),
  KEY `idx_cuenta_cobrar_usuario` (`usuario_id`),
  KEY `fk_cuenta_cobrar_anulado_usuario` (`anulado_usuario`),
  CONSTRAINT `fk_cuenta_cobrar_anulado_usuario` FOREIGN KEY (`anulado_usuario`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_cobrar_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_cobrar_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_cobrar_moneda` FOREIGN KEY (`moneda_id`) REFERENCES `moneda` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_cobrar_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_cuenta_cobrar_credito_dias` CHECK ((`credito_dias` >= 0)),
  CONSTRAINT `chk_cuenta_cobrar_importes` CHECK (((`total` > 0) and (`abono` >= 0) and (`saldo` >= 0) and (round((`abono` + `saldo`),5) = round(`total`,5))))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- cuenta_cobrar_pago
CREATE TABLE `cuenta_cobrar_pago` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cuenta_cobrar_id` int NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `forma_pago_id` int NOT NULL,
  `recibo_numero` varchar(30) DEFAULT NULL,
  `documento_fecha` date DEFAULT NULL,
  `documento_numero` varchar(30) DEFAULT NULL,
  `documento_comprobante` varchar(250) DEFAULT NULL,
  `total` decimal(15,5) NOT NULL,
  `usuario_id` int NOT NULL,
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  `anulado_fecha` datetime DEFAULT NULL,
  `anulado_motivo` varchar(200) DEFAULT NULL,
  `anulado_usuario` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cuenta_cobrar_pago_recibo` (`recibo_numero`),
  KEY `idx_cuenta_cobrar_pago_cuenta` (`cuenta_cobrar_id`,`anulado`),
  KEY `idx_cuenta_cobrar_pago_forma` (`forma_pago_id`),
  KEY `idx_cuenta_cobrar_pago_usuario` (`usuario_id`),
  KEY `fk_cuenta_cobrar_pago_anulado_usuario` (`anulado_usuario`),
  CONSTRAINT `fk_cuenta_cobrar_pago_anulado_usuario` FOREIGN KEY (`anulado_usuario`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_cobrar_pago_cuenta` FOREIGN KEY (`cuenta_cobrar_id`) REFERENCES `cuenta_cobrar` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_cobrar_pago_forma` FOREIGN KEY (`forma_pago_id`) REFERENCES `forma_pago` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_cobrar_pago_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_cuenta_cobrar_pago_total` CHECK ((`total` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- empresa_parametro
CREATE TABLE `empresa_parametro` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa_id` int NOT NULL,
  `moneda_id` int DEFAULT NULL,
  `abr_recepcion` varchar(5) DEFAULT NULL,
  `abr_producto` varchar(5) DEFAULT NULL,
  `abr_cotizacion` varchar(5) DEFAULT NULL,
  `abr_compra` varchar(5) DEFAULT NULL,
  `abr_venta` varchar(5) DEFAULT NULL,
  `abr_recibo` varchar(5) DEFAULT NULL,
  `decimal_cantidad` int DEFAULT NULL,
  `decimal_monto` int DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_empresa_parametro_empresa1_idx` (`empresa_id`),
  KEY `idx_empresa_parametro_moneda` (`moneda_id`),
  CONSTRAINT `fk_empresa_parametro_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`),
  CONSTRAINT `fk_empresa_parametro_moneda` FOREIGN KEY (`moneda_id`) REFERENCES `moneda` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- inventario_ajuste_tipo
CREATE TABLE `inventario_ajuste_tipo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(45) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `movimiento_tipo_id` int NOT NULL,
  `requiere_observacion` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ajuste_tipo_empresa_codigo` (`empresa_id`,`codigo`),
  UNIQUE KEY `uq_ajuste_tipo_id_empresa` (`id`,`empresa_id`),
  KEY `idx_ajuste_tipo_movimiento_tipo` (`movimiento_tipo_id`,`empresa_id`),
  CONSTRAINT `fk_ajuste_tipo_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ajuste_tipo_movimiento_tipo` FOREIGN KEY (`movimiento_tipo_id`, `empresa_id`) REFERENCES `movimiento_tipo` (`id`, `empresa_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- producto
CREATE TABLE `producto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(150) NOT NULL,
  `nombre` varchar(300) NOT NULL,
  `descripcion` text NOT NULL,
  `tipo_producto` varchar(1) NOT NULL DEFAULT 'B',
  `codigo_barra` varchar(100) DEFAULT NULL,
  `precio` decimal(10,5) DEFAULT NULL,
  `costo` decimal(10,5) DEFAULT NULL,
  `foto` varchar(350) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `control_vence` tinyint(1) NOT NULL DEFAULT '0',
  `existencia_minima` decimal(10,2) DEFAULT '0.00',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `marca_id` int NOT NULL,
  `unidad_medida_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `categoria_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_producto_marca1_idx` (`marca_id`),
  KEY `fk_producto_unidad_medida1_idx` (`unidad_medida_id`),
  KEY `fk_producto_empresa1_idx` (`empresa_id`),
  KEY `fk_producto_categoria1_idx` (`categoria_id`),
  KEY `fk_producto_usuario1_idx` (`usuario_id`),
  CONSTRAINT `fk_producto_categoria1` FOREIGN KEY (`categoria_id`) REFERENCES `categoria` (`id`),
  CONSTRAINT `fk_producto_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`),
  CONSTRAINT `fk_producto_marca1` FOREIGN KEY (`marca_id`) REFERENCES `marca` (`id`),
  CONSTRAINT `fk_producto_unidad_medida1` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`),
  CONSTRAINT `fk_producto_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- producto_presentacion
CREATE TABLE `producto_presentacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `factor` decimal(10,5) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `producto_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_producto_presentacion_producto1_idx` (`producto_id`),
  KEY `fk_producto_presentacion_usuario1_idx` (`usuario_id`),
  CONSTRAINT `fk_producto_presentacion_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`),
  CONSTRAINT `fk_producto_presentacion_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- proveedor
CREATE TABLE `proveedor` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `identificacion` varchar(100) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `correo` varchar(250) DEFAULT NULL,
  `credito` tinyint(1) NOT NULL DEFAULT '0',
  `credito_limite` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `credito_dias` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_proveedor_empresa1_idx` (`empresa_id`),
  KEY `fk_proveedor_usuario1_idx` (`usuario_id`),
  CONSTRAINT `fk_proveedor_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`),
  CONSTRAINT `fk_proveedor_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- sucursal
CREATE TABLE `sucursal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `correo` varchar(250) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  `municipio_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sucursal_empresa1_idx` (`empresa_id`),
  KEY `fk_sucursal_municipio1_idx` (`municipio_id`),
  KEY `fk_sucursal_usuario1_idx` (`usuario_id`),
  CONSTRAINT `fk_sucursal_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`),
  CONSTRAINT `fk_sucursal_municipio1` FOREIGN KEY (`municipio_id`) REFERENCES `municipio` (`id`),
  CONSTRAINT `fk_sucursal_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- usuario_sucursal
CREATE TABLE `usuario_sucursal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `sucursal_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_usuario_sucursal_sucursal1_idx` (`sucursal_id`),
  KEY `fk_usuario_sucursal_usuario1_idx` (`usuario_id`),
  CONSTRAINT `fk_usuario_sucursal_sucursal1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`),
  CONSTRAINT `fk_usuario_sucursal_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- compra
CREATE TABLE `compra` (
  `id` int NOT NULL AUTO_INCREMENT,
  `compra_estado_id` int NOT NULL,
  `proveedor_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `forma_pago_id` int NOT NULL,
  `moneda_id` int NOT NULL,
  `sucursal_id` int NOT NULL,
  `numero` varchar(30) NOT NULL,
  `factura_numero` varchar(50) DEFAULT NULL,
  `factura_fecha` date DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_costo` decimal(15,2) NOT NULL DEFAULT '0.00',
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_anulado` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_compra_compra_estado1_idx` (`compra_estado_id`),
  KEY `fk_compra_proveedor1_idx` (`proveedor_id`),
  KEY `fk_compra_empresa1_idx` (`empresa_id`),
  KEY `fk_compra_usuario1_idx` (`usuario_id`),
  KEY `fk_compra_forma_pago1_idx` (`forma_pago_id`),
  KEY `fk_compra_moneda1_idx` (`moneda_id`),
  KEY `fk_compra_sucursal1_idx` (`sucursal_id`),
  CONSTRAINT `fk_compra_compra_estado1` FOREIGN KEY (`compra_estado_id`) REFERENCES `compra_estado` (`id`),
  CONSTRAINT `fk_compra_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`),
  CONSTRAINT `fk_compra_forma_pago1` FOREIGN KEY (`forma_pago_id`) REFERENCES `forma_pago` (`id`),
  CONSTRAINT `fk_compra_moneda1` FOREIGN KEY (`moneda_id`) REFERENCES `moneda` (`id`),
  CONSTRAINT `fk_compra_proveedor1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedor` (`id`),
  CONSTRAINT `fk_compra_sucursal1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`),
  CONSTRAINT `fk_compra_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- compra_detalle
CREATE TABLE `compra_detalle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `compra_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `unidad_medida_id` int NOT NULL,
  `producto_presentacion_id` int DEFAULT NULL,
  `cantidad` decimal(15,2) NOT NULL DEFAULT '0.00',
  `precio_costo` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_costo` decimal(15,2) NOT NULL DEFAULT '0.00',
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_vence` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_compra_detalle_producto1_idx` (`producto_id`),
  KEY `fk_compra_detalle_unidad_medida1_idx` (`unidad_medida_id`),
  KEY `fk_compra_detalle_producto_presentacion1_idx` (`producto_presentacion_id`),
  KEY `fk_compra_detalle_compra1_idx` (`compra_id`),
  CONSTRAINT `fk_compra_detalle_compra1` FOREIGN KEY (`compra_id`) REFERENCES `compra` (`id`),
  CONSTRAINT `fk_compra_detalle_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`),
  CONSTRAINT `fk_compra_detalle_producto_presentacion1` FOREIGN KEY (`producto_presentacion_id`) REFERENCES `producto_presentacion` (`id`),
  CONSTRAINT `fk_compra_detalle_unidad_medida1` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- cotizacion
CREATE TABLE `cotizacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `empresa_id` int NOT NULL,
  `sucursal_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `vendedor_id` int DEFAULT NULL,
  `moneda_id` int NOT NULL,
  `cliente_id` int DEFAULT NULL,
  `forma_pago_id` int DEFAULT NULL,
  `cotizacion_estado_id` int NOT NULL,
  `cotizacion_serie_id` int NOT NULL,
  `numero_correlativo` int NOT NULL,
  `numero` varchar(30) NOT NULL,
  `valida_hasta` date NOT NULL,
  `cliente_nombre` varchar(150) NOT NULL,
  `cliente_razon_social` varchar(150) DEFAULT NULL,
  `cliente_identificacion` varchar(20) DEFAULT NULL,
  `cliente_direccion` varchar(200) DEFAULT NULL,
  `cliente_telefono` varchar(30) DEFAULT NULL,
  `cliente_correo` varchar(150) DEFAULT NULL,
  `subtotal` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `descuento` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `base` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `iva` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `isr` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `total` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `total_costo` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `ganancia` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `tipo_cambio` decimal(15,5) NOT NULL DEFAULT '1.00000',
  `referencia` varchar(300) DEFAULT NULL,
  `observaciones` text,
  `condiciones` text,
  `fecha_envio` datetime DEFAULT NULL,
  `fecha_aceptacion` datetime DEFAULT NULL,
  `fecha_rechazo` datetime DEFAULT NULL,
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  `anulado_fecha` datetime DEFAULT NULL,
  `anulado_usuario` int DEFAULT NULL,
  `anulado_motivo` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cotizacion_empresa_numero` (`empresa_id`,`numero`),
  UNIQUE KEY `uq_cotizacion_serie_correlativo` (`cotizacion_serie_id`,`numero_correlativo`),
  UNIQUE KEY `uq_cotizacion_id_empresa` (`id`,`empresa_id`),
  KEY `idx_cotizacion_estado_empresa` (`cotizacion_estado_id`,`empresa_id`),
  KEY `idx_cotizacion_serie_empresa` (`cotizacion_serie_id`,`empresa_id`),
  KEY `idx_cotizacion_sucursal` (`sucursal_id`),
  KEY `idx_cotizacion_usuario` (`usuario_id`),
  KEY `idx_cotizacion_vendedor` (`vendedor_id`),
  KEY `idx_cotizacion_moneda` (`moneda_id`),
  KEY `idx_cotizacion_cliente` (`cliente_id`),
  KEY `idx_cotizacion_forma_pago` (`forma_pago_id`),
  KEY `idx_cotizacion_valida_hasta` (`valida_hasta`),
  KEY `idx_cotizacion_anulado_usuario` (`anulado_usuario`),
  KEY `fk_cotizacion_cliente_empresa` (`cliente_id`,`empresa_id`),
  CONSTRAINT `fk_cotizacion_anulado_usuario` FOREIGN KEY (`anulado_usuario`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cotizacion_cliente_empresa` FOREIGN KEY (`cliente_id`, `empresa_id`) REFERENCES `cliente` (`id`, `empresa_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cotizacion_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cotizacion_estado_empresa` FOREIGN KEY (`cotizacion_estado_id`, `empresa_id`) REFERENCES `cotizacion_estado` (`id`, `empresa_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cotizacion_forma_pago` FOREIGN KEY (`forma_pago_id`) REFERENCES `forma_pago` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cotizacion_moneda` FOREIGN KEY (`moneda_id`) REFERENCES `moneda` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cotizacion_serie_empresa` FOREIGN KEY (`cotizacion_serie_id`, `empresa_id`) REFERENCES `cotizacion_serie` (`id`, `empresa_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cotizacion_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cotizacion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cotizacion_vendedor` FOREIGN KEY (`vendedor_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- cotizacion_detalle
CREATE TABLE `cotizacion_detalle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cotizacion_id` int NOT NULL,
  `orden` int NOT NULL DEFAULT '1',
  `producto_id` int NOT NULL,
  `unidad_medida_id` int NOT NULL,
  `producto_presentacion_id` int DEFAULT NULL,
  `producto_codigo` varchar(150) NOT NULL,
  `producto_nombre` varchar(300) NOT NULL,
  `producto_descripcion` varchar(300) DEFAULT NULL,
  `unidad_codigo` varchar(10) NOT NULL,
  `presentacion_nombre` varchar(50) DEFAULT NULL,
  `cantidad` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `precio` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `costo` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `subtotal` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `descuento_porcentaje` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `descuento_total` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `base` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `iva` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `isr` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `total` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `total_costo` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `ganancia` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `observacion` varchar(300) DEFAULT NULL,
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cotizacion_detalle_orden` (`cotizacion_id`,`orden`),
  KEY `idx_cotizacion_detalle_producto` (`producto_id`),
  KEY `idx_cotizacion_detalle_unidad` (`unidad_medida_id`),
  KEY `idx_cotizacion_detalle_presentacion` (`producto_presentacion_id`),
  CONSTRAINT `fk_cotizacion_detalle_presentacion` FOREIGN KEY (`producto_presentacion_id`) REFERENCES `producto_presentacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cotizacion_detalle_cotizacion` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cotizacion_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cotizacion_detalle_unidad` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- cuenta_pagar
CREATE TABLE `cuenta_pagar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `proveedor_id` int NOT NULL,
  `factura_fecha` date NOT NULL,
  `factura_numero` varchar(45) NOT NULL,
  `factura_documento` varchar(100) DEFAULT NULL,
  `credito_dias` int NOT NULL DEFAULT '0',
  `fecha_vence` date NOT NULL,
  `total` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `abono` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `saldo` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `moneda_id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `compra_id` int DEFAULT NULL,
  `referencia` varchar(300) DEFAULT NULL,
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  `anulado_fecha` datetime DEFAULT NULL,
  `anulado_motivo` varchar(300) DEFAULT NULL,
  `anulado_usuario` int DEFAULT NULL,
  `origen` int NOT NULL DEFAULT '1' COMMENT '1=Manual, 2=Orden de compra',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cuenta_pagar_empresa_compra` (`empresa_id`,`compra_id`),
  KEY `idx_cuenta_pagar_proveedor` (`proveedor_id`),
  KEY `idx_cuenta_pagar_empresa_estado` (`empresa_id`,`anulado`,`saldo`),
  KEY `idx_cuenta_pagar_vencimiento` (`empresa_id`,`fecha_vence`),
  KEY `idx_cuenta_pagar_moneda` (`moneda_id`),
  KEY `idx_cuenta_pagar_usuario` (`usuario_id`),
  KEY `idx_cuenta_pagar_compra` (`compra_id`),
  KEY `fk_cuenta_pagar_anulado_usuario` (`anulado_usuario`),
  CONSTRAINT `fk_cuenta_pagar_anulado_usuario` FOREIGN KEY (`anulado_usuario`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_pagar_compra` FOREIGN KEY (`compra_id`) REFERENCES `compra` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_pagar_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_pagar_moneda` FOREIGN KEY (`moneda_id`) REFERENCES `moneda` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_pagar_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedor` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_pagar_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_cuenta_pagar_credito_dias` CHECK ((`credito_dias` >= 0)),
  CONSTRAINT `chk_cuenta_pagar_importes` CHECK (((`total` > 0) and (`abono` >= 0) and (`saldo` >= 0) and (round((`abono` + `saldo`),5) = round(`total`,5))))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- cuenta_pagar_pago
CREATE TABLE `cuenta_pagar_pago` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cuenta_pagar_id` int NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `forma_pago_id` int NOT NULL,
  `comprobante_numero` varchar(30) DEFAULT NULL,
  `documento_fecha` date DEFAULT NULL,
  `documento_numero` varchar(30) DEFAULT NULL,
  `documento_comprobante` varchar(250) DEFAULT NULL,
  `total` decimal(15,5) NOT NULL,
  `usuario_id` int NOT NULL,
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  `anulado_fecha` datetime DEFAULT NULL,
  `anulado_motivo` varchar(200) DEFAULT NULL,
  `anulado_usuario` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cuenta_pagar_pago_comprobante` (`comprobante_numero`),
  KEY `idx_cuenta_pagar_pago_cuenta` (`cuenta_pagar_id`,`anulado`),
  KEY `idx_cuenta_pagar_pago_forma` (`forma_pago_id`),
  KEY `idx_cuenta_pagar_pago_usuario` (`usuario_id`),
  KEY `fk_cuenta_pagar_pago_anulado_usuario` (`anulado_usuario`),
  CONSTRAINT `fk_cuenta_pagar_pago_anulado_usuario` FOREIGN KEY (`anulado_usuario`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_pagar_pago_cuenta` FOREIGN KEY (`cuenta_pagar_id`) REFERENCES `cuenta_pagar` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_pagar_pago_forma` FOREIGN KEY (`forma_pago_id`) REFERENCES `forma_pago` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cuenta_pagar_pago_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_cuenta_pagar_pago_total` CHECK ((`total` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- inventario_ajuste
CREATE TABLE `inventario_ajuste` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero` varchar(45) NOT NULL,
  `inventario_ajuste_tipo_id` int NOT NULL,
  `inventario_ajuste_estado_id` int NOT NULL,
  `observacion` varchar(300) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_aplicado` datetime DEFAULT NULL,
  `fecha_anulado` datetime DEFAULT NULL,
  `anulado_motivo` varchar(500) DEFAULT NULL,
  `empresa_id` int NOT NULL,
  `sucursal_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `usuario_aplico_id` int DEFAULT NULL,
  `usuario_anulo_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventario_ajuste_empresa_numero` (`empresa_id`,`numero`),
  KEY `idx_inventario_ajuste_tipo_empresa` (`inventario_ajuste_tipo_id`,`empresa_id`),
  KEY `idx_inventario_ajuste_estado_empresa` (`inventario_ajuste_estado_id`,`empresa_id`),
  KEY `idx_inventario_ajuste_sucursal` (`sucursal_id`),
  KEY `idx_inventario_ajuste_usuario` (`usuario_id`),
  KEY `idx_inventario_ajuste_usuario_aplico` (`usuario_aplico_id`),
  KEY `idx_inventario_ajuste_usuario_anulo` (`usuario_anulo_id`),
  KEY `idx_inventario_ajuste_fecha` (`fecha`),
  CONSTRAINT `fk_inventario_ajuste_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_ajuste_estado_empresa` FOREIGN KEY (`inventario_ajuste_estado_id`, `empresa_id`) REFERENCES `inventario_ajuste_estado` (`id`, `empresa_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_ajuste_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_ajuste_tipo_empresa` FOREIGN KEY (`inventario_ajuste_tipo_id`, `empresa_id`) REFERENCES `inventario_ajuste_tipo` (`id`, `empresa_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_ajuste_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_ajuste_usuario_anulo` FOREIGN KEY (`usuario_anulo_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_ajuste_usuario_aplico` FOREIGN KEY (`usuario_aplico_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- inventario_ajuste_detalle
CREATE TABLE `inventario_ajuste_detalle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inventario_ajuste_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `unidad_medida_id` int NOT NULL,
  `producto_presentacion_id` int DEFAULT NULL,
  `cantidad` decimal(15,2) NOT NULL,
  `costo` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `fecha_vence` datetime DEFAULT NULL,
  `observacion` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ajuste_detalle_ajuste` (`inventario_ajuste_id`),
  KEY `idx_ajuste_detalle_producto` (`producto_id`),
  KEY `idx_ajuste_detalle_unidad` (`unidad_medida_id`),
  KEY `idx_ajuste_detalle_presentacion` (`producto_presentacion_id`),
  CONSTRAINT `fk_ajuste_detalle_ajuste` FOREIGN KEY (`inventario_ajuste_id`) REFERENCES `inventario_ajuste` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ajuste_detalle_presentacion` FOREIGN KEY (`producto_presentacion_id`) REFERENCES `producto_presentacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ajuste_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ajuste_detalle_unidad` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_ajuste_detalle_cantidad` CHECK ((`cantidad` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- inventario_enc
CREATE TABLE `inventario_enc` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero` varchar(45) NOT NULL,
  `inventario_tipo_id` int NOT NULL,
  `inventario_estado_id` int NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_corte` datetime DEFAULT NULL,
  `observacion` varchar(300) DEFAULT NULL,
  `archivo_nombre` varchar(255) DEFAULT NULL,
  `archivo_hash` char(64) DEFAULT NULL,
  `fecha_procesado` datetime DEFAULT NULL,
  `fecha_anulado` datetime DEFAULT NULL,
  `empresa_id` int NOT NULL,
  `sucursal_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `usuario_proceso_id` int DEFAULT NULL,
  `usuario_anulo_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventario_enc_empresa_numero` (`empresa_id`,`numero`),
  KEY `idx_inventario_enc_tipo_empresa` (`inventario_tipo_id`,`empresa_id`),
  KEY `idx_inventario_enc_estado_empresa` (`inventario_estado_id`,`empresa_id`),
  KEY `idx_inventario_enc_sucursal` (`sucursal_id`),
  KEY `idx_inventario_enc_usuario` (`usuario_id`),
  KEY `idx_inventario_enc_usuario_proceso` (`usuario_proceso_id`),
  KEY `idx_inventario_enc_usuario_anulo` (`usuario_anulo_id`),
  KEY `idx_inventario_enc_fecha` (`fecha`),
  KEY `idx_inventario_enc_fecha_corte` (`fecha_corte`),
  KEY `idx_inventario_enc_archivo_hash` (`empresa_id`,`archivo_hash`),
  CONSTRAINT `fk_inventario_enc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_enc_estado_empresa` FOREIGN KEY (`inventario_estado_id`, `empresa_id`) REFERENCES `inventario_estado` (`id`, `empresa_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_enc_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_enc_tipo_empresa` FOREIGN KEY (`inventario_tipo_id`, `empresa_id`) REFERENCES `inventario_tipo` (`id`, `empresa_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_enc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_enc_usuario_anulo` FOREIGN KEY (`usuario_anulo_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_enc_usuario_proceso` FOREIGN KEY (`usuario_proceso_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- inventario_conversion
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

-- stock
CREATE TABLE `stock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `unidad_medida_id` int NOT NULL,
  `producto_presentacion_id` int DEFAULT NULL,
  `fecha_vence` datetime DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `usuario_id` int NOT NULL,
  `sucursal_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_stock_producto1_idx` (`producto_id`),
  KEY `fk_stock_unidad_medida1_idx` (`unidad_medida_id`),
  KEY `fk_stock_producto_presentacion1_idx` (`producto_presentacion_id`),
  KEY `fk_stock_usuario1_idx` (`usuario_id`),
  KEY `fk_stock_sucursal1_idx` (`sucursal_id`),
  CONSTRAINT `fk_stock_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`),
  CONSTRAINT `fk_stock_producto_presentacion1` FOREIGN KEY (`producto_presentacion_id`) REFERENCES `producto_presentacion` (`id`),
  CONSTRAINT `fk_stock_sucursal1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`),
  CONSTRAINT `fk_stock_unidad_medida1` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`),
  CONSTRAINT `fk_stock_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- venta
CREATE TABLE `venta` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `empresa_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `moneda_id` int NOT NULL,
  `cliente_id` int DEFAULT NULL COMMENT 'Sin FK hasta implementar la tabla cliente',
  `cotizacion_id` int DEFAULT NULL,
  `forma_pago_id` int NOT NULL,
  `venta_estado_id` int NOT NULL,
  `venta_serie_id` int NOT NULL,
  `vendedor_id` int DEFAULT NULL COMMENT 'Sin FK hasta definir el catalogo de vendedores',
  `total_precio` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `total_costo` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `ganancia` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `base` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `iva` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `isr` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `descuento` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `tipo_cambio` decimal(15,5) NOT NULL DEFAULT '1.00000',
  `correlativo` varchar(15) DEFAULT NULL,
  `referencia` varchar(300) DEFAULT NULL,
  `factura_fecha` date DEFAULT NULL,
  `factura_numero` varchar(20) DEFAULT NULL,
  `factura_serie` varchar(20) DEFAULT NULL,
  `factura_uuid` varchar(50) DEFAULT NULL,
  `certificada` tinyint(1) NOT NULL DEFAULT '0',
  `certificada_fecha` datetime DEFAULT NULL,
  `certificada_usuario` int DEFAULT NULL,
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  `anulado_fecha` datetime DEFAULT NULL,
  `anulado_usuario` int DEFAULT NULL,
  `anulado_motivo` varchar(500) DEFAULT NULL,
  `sucursal_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_venta_empresa_correlativo` (`empresa_id`,`correlativo`),
  UNIQUE KEY `uq_venta_empresa_cotizacion` (`empresa_id`,`cotizacion_id`),
  KEY `idx_venta_estado` (`venta_estado_id`),
  KEY `idx_venta_serie_empresa` (`venta_serie_id`,`empresa_id`),
  KEY `idx_venta_usuario` (`usuario_id`),
  KEY `idx_venta_moneda` (`moneda_id`),
  KEY `idx_venta_cliente` (`cliente_id`),
  KEY `idx_venta_forma_pago` (`forma_pago_id`),
  KEY `idx_venta_vendedor` (`vendedor_id`),
  KEY `idx_venta_certificada_usuario` (`certificada_usuario`),
  KEY `idx_venta_anulado_usuario` (`anulado_usuario`),
  KEY `fk_venta_sucursal_idx` (`sucursal_id`),
  KEY `fk_venta_cotizacion_empresa` (`cotizacion_id`,`empresa_id`),
  KEY `fk_venta_cotizacion_idx` (`cotizacion_id`),
  CONSTRAINT `fk_venta_anulado_usuario` FOREIGN KEY (`anulado_usuario`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_certificada_usuario` FOREIGN KEY (`certificada_usuario`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_cotizacion` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizacion` (`id`),
  CONSTRAINT `fk_venta_cotizacion_empresa` FOREIGN KEY (`cotizacion_id`, `empresa_id`) REFERENCES `cotizacion` (`id`, `empresa_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_estado` FOREIGN KEY (`venta_estado_id`) REFERENCES `venta_estado` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_forma_pago` FOREIGN KEY (`forma_pago_id`) REFERENCES `forma_pago` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_moneda` FOREIGN KEY (`moneda_id`) REFERENCES `moneda` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_serie_empresa` FOREIGN KEY (`venta_serie_id`, `empresa_id`) REFERENCES `venta_serie` (`id`, `empresa_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`),
  CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- venta_detalle
CREATE TABLE `venta_detalle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `venta_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `unidad_medida_id` int NOT NULL,
  `producto_presentacion_id` int DEFAULT NULL,
  `producto_precio_costo_id` int DEFAULT NULL COMMENT 'Sin FK hasta implementar la tabla producto_precio_costo',
  `cantidad` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `precio` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `costo` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `total_precio` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `total_costo` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `ganancia` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `base` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `iva` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `isr` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `descuento` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `descuento_total` decimal(15,5) NOT NULL DEFAULT '0.00000',
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_venta_detalle_venta` (`venta_id`),
  KEY `idx_venta_detalle_producto` (`producto_id`),
  KEY `idx_venta_detalle_unidad` (`unidad_medida_id`),
  KEY `idx_venta_detalle_precio_costo` (`producto_precio_costo_id`),
  KEY `idx_venta_detalle_presentacion` (`producto_presentacion_id`),
  CONSTRAINT `fk_venta_detalle_presentacion` FOREIGN KEY (`producto_presentacion_id`) REFERENCES `producto_presentacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_detalle_unidad` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_detalle_venta` FOREIGN KEY (`venta_id`) REFERENCES `venta` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- inventario_det
CREATE TABLE `inventario_det` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inventario_enc_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `unidad_medida_id` int NOT NULL,
  `producto_presentacion_id` int DEFAULT NULL,
  `fecha_vence` datetime DEFAULT NULL,
  `cantidad_sistema` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cantidad_fisica` decimal(10,2) NOT NULL,
  `diferencia` decimal(10,2) NOT NULL,
  `costo` decimal(10,5) DEFAULT NULL,
  `observacion` varchar(300) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_inventario_det_enc` (`inventario_enc_id`),
  KEY `idx_inventario_det_producto` (`producto_id`),
  KEY `idx_inventario_det_unidad` (`unidad_medida_id`),
  KEY `idx_inventario_det_presentacion` (`producto_presentacion_id`),
  CONSTRAINT `fk_inventario_det_enc` FOREIGN KEY (`inventario_enc_id`) REFERENCES `inventario_enc` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_det_presentacion` FOREIGN KEY (`producto_presentacion_id`) REFERENCES `producto_presentacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_det_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventario_det_unidad` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_inventario_det_cantidad_fisica` CHECK ((`cantidad_fisica` >= 0)),
  CONSTRAINT `chk_inventario_det_cantidad_sistema` CHECK ((`cantidad_sistema` >= 0)),
  CONSTRAINT `chk_inventario_det_diferencia` CHECK ((`diferencia` = (`cantidad_fisica` - `cantidad_sistema`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- movimiento
CREATE TABLE `movimiento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `stock_id` int NOT NULL,
  `movimiento_tipo_id` int NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `compra_id` int DEFAULT NULL,
  `inventario_ajuste_detalle_id` int DEFAULT NULL,
  `inventario_det_id` int DEFAULT NULL,
  `venta_detalle_id` int DEFAULT NULL,
  `inventario_conversion_id` int DEFAULT NULL,
  `usuario_id` int NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observacion` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_movimiento_stock1_idx` (`stock_id`),
  KEY `fk_movimiento_compra1_idx` (`compra_id`),
  KEY `fk_movimiento_movimiento_tipo1_idx` (`movimiento_tipo_id`),
  KEY `fk_movimiento_usuario1_idx` (`usuario_id`),
  KEY `idx_movimiento_inventario_det` (`inventario_det_id`),
  KEY `idx_movimiento_venta_detalle` (`venta_detalle_id`),
  KEY `idx_movimiento_inventario_ajuste_detalle` (`inventario_ajuste_detalle_id`),
  KEY `idx_movimiento_inventario_conversion` (`inventario_conversion_id`),
  CONSTRAINT `fk_movimiento_compra1` FOREIGN KEY (`compra_id`) REFERENCES `compra` (`id`),
  CONSTRAINT `fk_movimiento_inventario_ajuste_detalle` FOREIGN KEY (`inventario_ajuste_detalle_id`) REFERENCES `inventario_ajuste_detalle` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_movimiento_inventario_conversion` FOREIGN KEY (`inventario_conversion_id`) REFERENCES `inventario_conversion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_movimiento_inventario_det` FOREIGN KEY (`inventario_det_id`) REFERENCES `inventario_det` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_movimiento_movimiento_tipo1` FOREIGN KEY (`movimiento_tipo_id`) REFERENCES `movimiento_tipo` (`id`),
  CONSTRAINT `fk_movimiento_stock1` FOREIGN KEY (`stock_id`) REFERENCES `stock` (`id`),
  CONSTRAINT `fk_movimiento_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `fk_movimiento_venta_detalle` FOREIGN KEY (`venta_detalle_id`) REFERENCES `venta_detalle` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

SET FOREIGN_KEY_CHECKS = 1;
