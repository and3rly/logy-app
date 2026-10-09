-- Equivalencias entre unidades de medida (decisión 0029): 1 Quintal = 100 Libras, 1 Galón = 3.785 Litros...
-- Una presentación puede ser otra unidad de medida (ej. Libra en un producto con base Quintal):
-- se elige de la lista y el factor sale de la equivalencia. NULL = empaque (Caja 12).
-- Para una base que ya existe; en una nueva basta estructura.sql + /instalacion.

CREATE TABLE `unidad_equivalencia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unidad_medida_id` int NOT NULL COMMENT 'La grande: 1 de esta...',
  `unidad_menor_id` int NOT NULL COMMENT '...trae "cantidad" de esta',
  `cantidad` decimal(15,5) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `empresa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_unidad_equivalencia_par` (`unidad_medida_id`,`unidad_menor_id`),
  KEY `idx_unidad_equivalencia_menor` (`unidad_menor_id`),
  KEY `idx_unidad_equivalencia_empresa` (`empresa_id`),
  CONSTRAINT `fk_unidad_equivalencia_unidad` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_unidad_equivalencia_menor` FOREIGN KEY (`unidad_menor_id`) REFERENCES `unidad_medida` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_unidad_equivalencia_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

ALTER TABLE `producto_presentacion`
  ADD COLUMN `unidad_medida_id` int DEFAULT NULL AFTER `factor`,
  ADD KEY `idx_producto_presentacion_unidad_medida` (`unidad_medida_id`),
  ADD CONSTRAINT `fk_producto_presentacion_unidad_medida` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Equivalencias comunes, en cada empresa que ya tenga las dos unidades (se buscan por código)
INSERT INTO `unidad_equivalencia` (`unidad_medida_id`, `unidad_menor_id`, `cantidad`, `empresa_id`)
SELECT g.id, p.id, e.cantidad, g.empresa_id
FROM (
  SELECT 'QQ' AS grande, 'LB' AS menor, 100 AS cantidad
  UNION ALL SELECT 'QQ', 'ARR', 4
  UNION ALL SELECT 'ARR', 'LB', 25
  UNION ALL SELECT 'LB', 'OZ', 16
  UNION ALL SELECT 'KG', 'LB', 2.20462
  UNION ALL SELECT 'GL', 'LT', 3.785
  UNION ALL SELECT 'LT', 'ML', 1000
  UNION ALL SELECT 'DOC', 'UND', 12
) e
JOIN `unidad_medida` g ON g.codigo = e.grande AND g.activo = 1
JOIN `unidad_medida` p ON p.codigo = e.menor AND p.activo = 1 AND p.empresa_id = g.empresa_id;
