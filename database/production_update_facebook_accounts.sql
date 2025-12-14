-- SQL para agregar el campo is_oauth_primary en Producción
-- Ejecutar en el cliente PostgreSQL o vía herramienta de administración

-- 1. Agregar la columna
ALTER TABLE facebook_accounts 
ADD COLUMN IF NOT EXISTS is_oauth_primary BOOLEAN DEFAULT FALSE;

-- 2. Asegurarse que solo una cuenta sea primaria (opcional, para inicializar)
-- Esto marca la primera cuenta activa como primaria si no hay ninguna
UPDATE facebook_accounts
SET is_oauth_primary = TRUE
WHERE id = (
    SELECT id FROM facebook_accounts 
    WHERE is_active = TRUE 
    AND app_id IS NOT NULL 
    ORDER BY created_at ASC 
    LIMIT 1
)
AND NOT EXISTS (
    SELECT 1 FROM facebook_accounts WHERE is_oauth_primary = TRUE
);

-- 3. Verificar
-- SELECT id, account_name, is_active, is_oauth_primary FROM facebook_accounts;
