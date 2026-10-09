-- Formato de impresión de la venta, por empresa (pantalla Parámetros):
-- 1 = ticket de 80 mm (el de siempre), 2 = carta. Los números los define Empresa_parametro_model.
-- Las empresas existentes quedan en 1 y siguen imprimiendo igual.
-- Se puede ejecutar de nuevo: solo agrega la columna si no existe.

SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'empresa_parametro' AND COLUMN_NAME = 'formato_impresion');
SET @sql := IF(@existe = 0,
  'ALTER TABLE `empresa_parametro` ADD COLUMN `formato_impresion` tinyint NOT NULL DEFAULT ''1'' AFTER `decimal_monto`',
  'SELECT 1');
PREPARE agregar_columna FROM @sql;
EXECUTE agregar_columna;
DEALLOCATE PREPARE agregar_columna;
