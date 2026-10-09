-- Hora de Guatemala (UTC-6, sin horario de verano) en el servidor MySQL.
-- Afecta a TODAS las bases del servidor (cada cliente por subdominio) y a toda conexión nueva:
-- la API, Workbench y las consultas manuales. PHP ya usa la misma zona (config/config.php).
-- Se usa '-06:00' y no 'America/Guatemala' porque el nombre necesita las tablas de zonas horarias
-- cargadas en MySQL; Guatemala no cambia de hora, así que es equivalente.
-- Requiere un usuario con privilegio SYSTEM_VARIABLES_ADMIN (o SUPER), ej. root.

-- 1. Antes: zona actual del servidor (anote el resultado)
SELECT @@global.time_zone, @@system_time_zone, NOW(), UTC_TIMESTAMP();

-- 2. Cambiar la zona. PERSIST (MySQL 8) la conserva aunque se reinicie el servidor.
SET PERSIST time_zone = '-06:00';

-- 3. Después: abrir una conexión NUEVA (la actual sigue con la zona anterior) y verificar.
--    NOW() debe ser la hora de Guatemala, 6 horas menos que UTC_TIMESTAMP().
-- SELECT @@global.time_zone, NOW(), UTC_TIMESTAMP();

-- Para volver atrás: RESET PERSIST time_zone; y reiniciar MySQL (o SET GLOBAL time_zone = 'SYSTEM';).
