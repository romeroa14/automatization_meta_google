"""Ingestion service for document processing"""
from typing import List
from uuid import UUID
from sqlalchemy import text
from langchain_text_splitters import RecursiveCharacterTextSplitter
from langchain_openai import OpenAIEmbeddings
import os

from app.core.db import async_session
from app.core.config import settings


async def ingest_document(
    document_id: UUID,
    file_path: str,
    file_type: str,
    chunk_size: int = 1000,
    overlap: int = 200,
) -> int:
    """Ingest a document: read, chunk, embed, and store"""
    # Read file content
    with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
        text_content = f.read()

    # Chunk text
    splitter = RecursiveCharacterTextSplitter(
        chunk_size=chunk_size,
        chunk_overlap=overlap,
        length_function=len,
    )
    chunks = splitter.split_text(text_content)

    # Get embeddings
    embeddings = OpenAIEmbeddings(api_key=settings.openai_api_key)

    # Store chunks with embeddings
    async with async_session() as session:
        for i, chunk_text in enumerate(chunks):
            # Get embedding
            embedding = embeddings.embed_documents([chunk_text])[0]

            await session.execute(
                text("""
                    INSERT INTO document_chunks (
                        document_id, organization_id, chunk_text, chunk_index,
                        embedding, created_at
                    ) VALUES (
                        :document_id, :organization_id, :chunk_text, :chunk_index,
                        :embedding, NOW()
                    )
                """),
                {
                    "document_id": document_id,
                    "organization_id": get_org_id_from_doc(document_id),
                    "chunk_text": chunk_text,
                    "chunk_index": i,
                    "embedding": f"[{','.join(map(str, embedding))}]",
                },
            )

        await session.commit()

    return len(chunks)


def get_org_id_from_doc(document_id: UUID) -> UUID:
    """Get organization_id from document"""
    # This is a placeholder - in production, would query the document
    return UUID("00000000-0000-0000-0000-000000000000")


async def search_chunks(
    organization_id: UUID,
    query: str,
    k: int = 5,
) -> List[dict]:
    """Search document chunks by query"""
    from app.core.config import settings
    from langchain_openai import OpenAIEmbeddings

    embeddings = OpenAIEmbeddings(api_key=settings.openai_api_key)
    query_embedding = embeddings.embed_query(query)

    async with async_session() as session:
        result = await session.execute(
            text("""
                SELECT dc.id, dc.chunk_text, dc.chunk_index, d.filename,
                       1 - (dc.embedding <=> :embedding::vector) as cosine_sim
                FROM document_chunks dc
                JOIN documents d ON dc.document_id = d.id
                WHERE dc.organization_id = :organization_id
                  AND d.is_active = TRUE
                ORDER BY dc.embedding <=> :embedding::vector
                LIMIT :k
            """),
            {
                "organization_id": organization_id,
                "embedding": f"[{','.join(map(str, query_embedding))}]",
                "k": k,
            },
        )
        rows = result.fetchall()

        return [
            {
                "chunk_id": row[0],
                "chunk_text": row[1],
                "chunk_index": row[2],
                "filename": row[3],
                "score": float(row[4]) if row[4] else 0.0,
            }
            for row in rows
        ]