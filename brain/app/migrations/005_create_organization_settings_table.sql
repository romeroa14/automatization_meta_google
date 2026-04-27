-- Migration: 005_create_organization_settings_table.sql
-- Phase 1.5 - Organization settings for per-org API keys and config
-- Created: 2026-04-23

BEGIN;

-- Create organization_settings table
CREATE TABLE IF NOT EXISTS organization_settings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID UNIQUE NOT NULL,
    openai_api_key_encrypted TEXT,
    custom_instructions TEXT,
    rate_limit_override INT,
    features JSONB DEFAULT '{}',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Create index for organization_settings
CREATE INDEX IF NOT EXISTS idx_org_settings_org ON organization_settings(organization_id);

COMMIT;