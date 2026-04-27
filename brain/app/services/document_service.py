"""Document service - upload, list, and manage documents"""
from typing import List, Optional
from uuid import UUID
from datetime import datetime
from sqlalchemy import text

from app.schemas.documents import (
    DocumentCreate,
    DocumentResponse,
    DocumentListResponse,
    DocumentReingestRequest,
)
from app.core.db import async_session
from app.core.config import settings
import os
import aiofiles


class DocumentService:
    """Service for document management"""

    def __init__(self, organization_id: UUID):
        self.organization_id = organization_id

    async def upload(
        self, data: DocumentCreate, file_content: bytes
    ) -> DocumentResponse:
        """Upload a document and trigger ingestion"""
        # Save file to storage
        storage_dir = os.path.join(
            settings.storage_path, "documents", str(self.organization_id)
        )
        os.makedirs(storage_dir, exist_ok=True)

        file_path = os.path.join(storage_dir, data.filename)

        # Write file
        async with aiofiles.open(file_path, "wb") as f:
            await f.write(file_content)

        # Save metadata to DB
        async with async_session() as session:
            result = await session.execute(
                text("""
                    INSERT INTO documents (
                        organization_id, filename, file_path, file_type,
                        file_size, doc_metadata, is_active, created_at, updated_at
                    ) VALUES (
                        :organization_id, :filename, :file_path, :file_type,
                        :file_size, :doc_metadata, TRUE, NOW(), NOW()
                    )
                    RETURNING id, organization_id, filename, file_path, file_type,
                              file_size, doc_metadata, is_active, created_at, updated_at
                """),
                {
                    "organization_id": self.organization_id,
                    "filename": data.filename,
                    "file_path": file_path,
                    "file_type": data.file_type,
                    "file_size": len(file_content),
                    "doc_metadata": data.doc_metadata,
                },
            )
            row = result.fetchone()
            await session.commit()

            doc = DocumentResponse(
                id=row[0],
                organization_id=row[1],
                filename=row[2],
                file_path=row[3],
                file_type=row[4],
                file_size=row[5],
                doc_metadata=row[6],
                is_active=row[7],
                created_at=row[8],
                updated_at=row[9],
            )

        # Trigger async ingestion (fire and forget - would use Celery in production)
        await self._trigger_ingestion(doc.id, file_path, data.file_type)

        return doc

    async def _trigger_ingestion(
        self, document_id: UUID, file_path: str, file_type: str
    ) -> None:
        """Trigger async document ingestion"""
        # This would typically use Celery or similar
        # For now, we'll do a simple fire-and-forget
        import asyncio

        async def ingest():
            try:
                from app.services.ingestion import ingest_document

                await ingest_document(document_id, file_path, file_type)
            except Exception as e:
                print(f"Error ingesting document {document_id}: {e}")

        # Schedule ingestion
        asyncio.create_task(ingest())

    async def list(self) -> DocumentListResponse:
        """List all documents for organization"""
        async with async_session() as session:
            result = await session.execute(
                text("""
                    SELECT id, organization_id, filename, file_path, file_type,
                           file_size, doc_metadata, is_active, created_at, updated_at
                    FROM documents
                    WHERE organization_id = :organization_id
                    ORDER BY created_at DESC
                """),
                {"organization_id": self.organization_id},
            )
            rows = result.fetchall()

            documents = [
                DocumentResponse(
                    id=row[0],
                    organization_id=row[1],
                    filename=row[2],
                    file_path=row[3],
                    file_type=row[4],
                    file_size=row[5],
                    doc_metadata=row[6],
                    is_active=row[7],
                    created_at=row[8],
                    updated_at=row[9],
                )
                for row in rows
            ]

            return DocumentListResponse(
                documents=documents,
                total=len(documents),
            )

    async def get(self, document_id: UUID) -> Optional[DocumentResponse]:
        """Get a document by ID"""
        async with async_session() as session:
            result = await session.execute(
                text("""
                    SELECT id, organization_id, filename, file_path, file_type,
                           file_size, doc_metadata, is_active, created_at, updated_at
                    FROM documents
                    WHERE id = :document_id
                      AND organization_id = :organization_id
                """),
                {
                    "document_id": document_id,
                    "organization_id": self.organization_id,
                },
            )
            row = result.fetchone()
            if not row:
                return None

            return DocumentResponse(
                id=row[0],
                organization_id=row[1],
                filename=row[2],
                file_path=row[3],
                file_type=row[4],
                file_size=row[5],
                doc_metadata=row[6],
                is_active=row[7],
                created_at=row[8],
                updated_at=row[9],
            )

    async def delete(self, document_id: UUID) -> bool:
        """Delete a document and its chunks"""
        async with async_session() as session:
            # Get file path first
            result = await session.execute(
                text("""
                    SELECT file_path FROM documents
                    WHERE id = :document_id
                      AND organization_id = :organization_id
                """),
                {
                    "document_id": document_id,
                    "organization_id": self.organization_id,
                },
            )
            row = result.fetchone()
            if not row:
                return False

            file_path = row[0]

            # Delete chunks (cascade should handle this)
            await session.execute(
                text("""
                    DELETE FROM document_chunks
                    WHERE document_id = :document_id
                """),
                {"document_id": document_id},
            )

            # Delete document
            await session.execute(
                text("""
                    DELETE FROM documents
                    WHERE id = :document_id
                """),
                {"document_id": document_id},
            )

            await session.commit()

            # Delete file
            if file_path and os.path.exists(file_path):
                os.remove(file_path)

            return True

    async def reingest(
        self, document_id: UUID, request: DocumentReingestRequest
    ) -> bool:
        """Re-ingest a document with new chunking parameters"""
        doc = await self.get(document_id)
        if not doc:
            return False

        # Delete existing chunks
        async with async_session() as session:
            await session.execute(
                text("""
                    DELETE FROM document_chunks
                    WHERE document_id = :document_id
                """),
                {"document_id": document_id},
            )
            await session.commit()

        # Re-trigger ingestion
        await self._trigger_ingestion(
            document_id, doc.file_path, doc.file_type
        )

        return True