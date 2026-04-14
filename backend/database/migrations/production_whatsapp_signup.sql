-- =============================================
-- WhatsApp Embedded Signup - Migration SQL
-- Admetricas Production Database (PostgreSQL)
-- Date: 2026-01-25
-- =============================================
-- 
-- Este script agrega los campos necesarios para 
-- WhatsApp Embedded Signup a la tabla existente
-- user_facebook_connections
--
-- IMPORTANTE: Ejecutar en producción
-- =============================================

-- Agregar campos de WhatsApp Business Account
ALTER TABLE user_facebook_connections 
ADD COLUMN IF NOT EXISTS waba_id VARCHAR(255) NULL;

ALTER TABLE user_facebook_connections 
ADD COLUMN IF NOT EXISTS business_id VARCHAR(255) NULL;

ALTER TABLE user_facebook_connections 
ADD COLUMN IF NOT EXISTS waba_data JSONB NULL;

ALTER TABLE user_facebook_connections 
ADD COLUMN IF NOT EXISTS signup_method VARCHAR(255) NULL;

-- Agregar comentarios a las columnas (sintaxis PostgreSQL)
COMMENT ON COLUMN user_facebook_connections.waba_id IS 'WhatsApp Business Account ID';
COMMENT ON COLUMN user_facebook_connections.business_id IS 'Facebook Business Portfolio ID';
COMMENT ON COLUMN user_facebook_connections.waba_data IS 'WhatsApp Business Account full data';
COMMENT ON COLUMN user_facebook_connections.signup_method IS 'embedded_signup, manual, etc.';

-- Crear índice para búsquedas eficientes por WABA
CREATE INDEX IF NOT EXISTS user_facebook_connections_waba_id_index 
ON user_facebook_connections (waba_id);

-- Verificar que los campos se agregaron correctamente
SELECT 
    column_name, 
    data_type, 
    is_nullable,
    column_default
FROM information_schema.columns 
WHERE table_name = 'user_facebook_connections' 
    AND column_name IN ('waba_id', 'business_id', 'waba_data', 'signup_method')
ORDER BY ordinal_position;

-- =============================================
-- Fin del script
-- =============================================
