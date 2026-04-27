-- Migration: 002_create_product_embeddings_table.sql
-- Phase 1.2 - Product embeddings table with VECTOR(1536) column
-- Created: 2026-04-23

BEGIN;

-- Create product_embeddings table
CREATE TABLE IF NOT EXISTS product_embeddings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    product_id UUID REFERENCES products(id) ON DELETE CASCADE,
    organization_id UUID NOT NULL,
    chunk_text TEXT NOT NULL,
    embedding VECTOR(1536),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Create indexes for product_embeddings
CREATE INDEX IF NOT EXISTS idx_embeddings_org ON product_embeddings(organization_id);
-- IVFFlat index for approximate vector search (when data grows)
-- Note: Requires sufficient data before creating, typically > 1000 rows
-- CREATE INDEX IF NOT EXISTS idx_embeddings_vector ON product_embeddings USING ivfflat (embedding organization_id_opclass);

COMMIT;