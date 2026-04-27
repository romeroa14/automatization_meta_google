"""RAG API routes"""
from fastapi import APIRouter, HTTPException
from uuid import UUID

from app.schemas.rag import (
    RAGQueryRequest,
    RAGQueryResponse,
    ChatRequest,
    ChatResponse,
    RAGHistoryRequest,
    RAGHistoryResponse,
)
from app.services.rag_service import RAGService
from app.core.rate_limit import rate_limiter

router = APIRouter(prefix="/organizations/{org_id}/rag", tags=["rag"])


def get_org_id(org_id: str) -> UUID:
    """Convert org_id string to UUID"""
    try:
        return UUID(org_id)
    except ValueError:
        raise HTTPException(status_code=400, detail="Invalid organization ID format")


@router.post("/query", response_model=RAGQueryResponse)
async def rag_query(
    org_id: str,
    request: RAGQueryRequest,
):
    """Query documents using RAG"""
    org_uuid = get_org_id(org_id)

    # Check rate limit
    if not rate_limiter.check_rate_limit(org_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    service = RAGService(org_uuid)
    return await service.query(request)


@router.post("/chat", response_model=ChatResponse)
async def rag_chat(
    org_id: str,
    request: ChatRequest,
):
    """Chat with RAG including conversation history"""
    org_uuid = get_org_id(org_id)

    # Check rate limit
    if not rate_limiter.check_rate_limit(org_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    service = RAGService(org_uuid)
    return await service.chat(request)


@router.get("/history", response_model=RAGHistoryResponse)
async def get_rag_history(
    org_id: str,
    conversation_id: str,
    limit: int = 20,
):
    """Get conversation history"""
    org_uuid = get_org_id(org_id)

    # Check rate limit
    if not rate_limiter.check_rate_limit(org_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    service = RAGService(org_uuid)
    return await service.get_history(
        RAGHistoryRequest(conversation_id=conversation_id, limit=limit)
    )