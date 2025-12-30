-- Script para limpiar leads y conversaciones
-- Mantener solo un lead de Alfredo Romero y limpiar todas las conversaciones

-- 1. Eliminar todas las conversaciones
DELETE FROM conversations;

-- 2. Eliminar todos los leads excepto uno de Alfredo Romero
-- Primero, encontrar el lead_id de Alfredo Romero que queremos mantener
-- (asumimos que es el más reciente o el que tiene id más bajo)

-- Opción A: Mantener el lead con id más bajo de Alfredo Romero
DELETE FROM leads 
WHERE phone_number = '584242536795' 
AND id NOT IN (
    SELECT MIN(id) FROM leads WHERE phone_number = '584242536795'
);

-- Opción B: Eliminar todos los leads de Alfredo Romero excepto el más reciente
-- DELETE FROM leads 
-- WHERE phone_number = '584242536795' 
-- AND id NOT IN (
--     SELECT id FROM leads 
--     WHERE phone_number = '584242536795' 
--     ORDER BY created_at DESC 
--     LIMIT 1
-- );

-- 3. Eliminar todos los demás leads (opcional, si quieres limpiar todo)
-- DELETE FROM leads WHERE phone_number != '584242536795';

-- 4. Verificar resultados
SELECT 
    id, 
    phone_number, 
    client_name, 
    created_at 
FROM leads 
WHERE phone_number = '584242536795';

SELECT COUNT(*) as total_conversations FROM conversations;

