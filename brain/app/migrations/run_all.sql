-- Run All Migrations
-- Phase 1.8 - Run Alembic migrations and verify tables created
-- This is a convenience script to run all migrations in order
-- Created: 2026-04-23

\i 001_create_products_table.sql
\i 002_create_product_embeddings_table.sql
\i 003_create_documents_table.sql
\i 004_create_document_chunks_table.sql
\i 005_create_organization_settings_table.sql

-- Verify tables created
SELECT tablename 
FROM pg_tables 
WHERE schemaname = 'public' 
AND tablename IN ('products', 'product_embeddings', 'documents', 'document_chunks', 'organization_settings');