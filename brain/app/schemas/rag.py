from pydantic import BaseModel, Field
from uuid import UUID
from typing import Optional, List, Dict, Any


class RAGQueryRequest(BaseModel):
    """Request schema for RAG query"""
    question: str = Field(..., min_length=1, max_length=2000)
    filters: Optional[Dict[str, Any]] = None
    max_chunks: int = Field(default=5, ge=1, le=20)


class SourceDocument(BaseModel):
    """Source document for RAG response"""
    document_id: UUID
    chunk_text: str
    score: float


class RAGQueryResponse(BaseModel):
    """Response schema for RAG query"""
    answer: str
    sources: List[SourceDocument]
    conversation_id: Optional[str] = None


class ChatMessage(BaseModel):
    """Single chat message"""
    role: str  # "user" or "assistant"
    content: str


class ChatRequest(BaseModel):
    """Request schema for RAG chat"""
    message: str = Field(..., min_length=1, max_length=2000)
    history: List[ChatMessage] = Field(default_factory=list)
    conversation_id: Optional[str] = None
    max_chunks: int = Field(default=5, ge=1, le=20)


class ChatResponse(BaseModel):
    """Response schema for RAG chat"""
    reply: str
    sources: List[SourceDocument] = Field(default_factory=list)
    conversation_id: str


class RAGHistoryRequest(BaseModel):
    """Request schema for getting chat history"""
    conversation_id: str
    limit: int = Field(default=20, ge=1, le=100)


class RAGHistoryResponse(BaseModel):
    """Response for chat history"""
    conversation_id: str
    messages: List[ChatMessage]
    total: int