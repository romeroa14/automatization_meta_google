"""RAG service for semantic query and chat"""
from typing import List, Optional, Dict, Any
from uuid import UUID
from datetime import datetime
from sqlalchemy import text

from app.schemas.rag import (
    RAGQueryRequest,
    RAGQueryResponse,
    ChatRequest,
    ChatResponse,
    RAGHistoryRequest,
    RAGHistoryResponse,
    ChatMessage,
    SourceDocument,
)
from app.core.db import async_session
from app.core.config import settings
from app.services.ingestion import search_chunks


def get_llm_client():
    """Get LLM client (DeepSeek or OpenAI)"""
    if settings.deepseek_api_key:
        # Use DeepSeek
        from openai import OpenAI
        return OpenAI(
            api_key=settings.deepseek_api_key,
            base_url=settings.deepseek_base_url
        ), settings.deepseek_model
    elif settings.openai_api_key:
        # Fallback to OpenAI
        from openai import OpenAI
        return OpenAI(api_key=settings.openai_api_key), "gpt-4o"
    else:
        raise ValueError("No LLM API key configured (DEEPSEEK_API_KEY or OPENAI_API_KEY)")


class RAGService:
    """Service for RAG query and chat"""

    def __init__(self, organization_id: UUID):
        self.organization_id = organization_id

    async def query(self, request: RAGQueryRequest) -> RAGQueryResponse:
        """Query RAG with retrieval + generation"""
        # Get relevant chunks
        chunks = await search_chunks(
            self.organization_id,
            request.question,
            k=request.max_chunks,
        )

        if not chunks:
            return RAGQueryResponse(
                answer="No relevant documents found.",
                sources=[],
                conversation_id=None,
            )

        # Build context from chunks
        context = "\n\n".join(
            f"Document: {c['filename']} (chunk {c['chunk_index'] + 1})\n{c['chunk_text']}"
            for c in chunks
        )

        # Generate answer using configured LLM
        client, model = get_llm_client()

        response = client.chat.completions.create(
            model=model,
            messages=[
                {
                    "role": "system",
                    "content": "You are a helpful assistant. Answer questions based only on the provided context.",
                },
                {
                    "role": "user",
                    "content": f"Context:\n{context}\n\nQuestion: {request.question}",
                },
            ],
            temperature=0.7,
            max_tokens=1000,
        )

        answer = response.choices[0].message.content

        # Build sources
        sources = [
            SourceDocument(
                document_id=UUID("00000000-0000-0000-0000-000000000000"),  # Would need proper mapping
                chunk_text=c["chunk_text"][:200] + "...",  # Truncate for response
                score=c["score"],
            )
            for c in chunks
        ]

        return RAGQueryResponse(
            answer=answer,
            sources=sources,
            conversation_id=None,
        )

    async def chat(self, request: ChatRequest) -> ChatResponse:
        """Chat with RAG including conversation history"""
        # Get relevant chunks
        chunks = await search_chunks(
            self.organization_id,
            request.message,
            k=request.max_chunks,
        )

        system_prompt = (
            "You are a helpful assistant. Answer questions based on the provided "
            "document context and conversation history."
        )

        messages = [{"role": "system", "content": system_prompt}]

        # Add conversation history
        for msg in request.history:
            messages.append({"role": msg.role, "content": msg.content})

        # Add context if available
        if chunks:
            context = "\n\n".join(
                f"Document: {c['filename']} (chunk {c['chunk_index'] + 1})\n{c['chunk_text']}"
                for c in chunks
            )
            messages.append(
                {
                    "role": "system",
                    "content": f"Relevant documents:\n{context}",
                }
            )

        # Add current message
        messages.append({"role": "user", "content": request.message})

        # Generate response using configured LLM
        client, model = get_llm_client()

        response = client.chat.completions.create(
            model=model,
            messages=messages,
            temperature=0.7,
            max_tokens=1000,
        )

        reply = response.choices[0].message.content

        # Build sources
        sources = [
            SourceDocument(
                document_id=UUID("00000000-0000-0000-0000-000000000000"),
                chunk_text=c["chunk_text"][:200] + "...",
                score=c["score"],
            )
            for c in chunks
        ]

        return ChatResponse(
            reply=reply,
            sources=sources,
            conversation_id=request.conversation_id
            or f"{self.organization_id}:chat:{datetime.now().timestamp()}",
        )

    async def get_history(
        self, request: RAGHistoryRequest
    ) -> RAGHistoryResponse:
        """Get conversation history"""
        async with async_session() as session:
            result = await session.execute(
                text("""
                    SELECT role, content, created_at
                    FROM rag_messages
                    WHERE conversation_id = :conversation_id
                    ORDER BY created_at DESC
                    LIMIT :limit
                """),
                {
                    "conversation_id": request.conversation_id,
                    "limit": request.limit,
                },
            )
            rows = result.fetchall()

            messages = [
                ChatMessage(role=row[0], content=row[1])
                for row in rows
            ]

            return RAGHistoryResponse(
                conversation_id=request.conversation_id,
                messages=messages,
                total=len(messages),
            )