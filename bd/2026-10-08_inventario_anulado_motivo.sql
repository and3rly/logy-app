-- Motivo de anulación del inventario inicial. Inventario_enc_model ya lo usa (anular() lo guarda y
-- guardar() lo envía en cada INSERT), pero la columna no estaba en estructura.sql: en una base creada
-- desde la estructura, importar un Excel fallaba con "No se pudo importar el archivo".
-- Se puede ejecutar de nuevo: solo agrega la columna si no existe.

SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventario_enc' AND COLUMN_NAME = 'anulado_motivo');
SET @sql := IF(@existe = 0,
  'ALTER TABLE `inventario_enc` ADD COLUMN `anulado_motivo` varchar(500) DEFAULT NULL AFTER `fecha_anulado`',
  'SELECT 1');
PREPARE agregar_columna FROM @sql;
EXECUTE agregar_columna;
DEALLOCATE PREPARE agregar_columna;
