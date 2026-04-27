from pydantic import BaseModel, Field
from uuid import UUID
from datetime import datetime
from typing import Optional, List


class DocumentCreate(BaseModel):
    """Schema for creating document metadata (file upload handled separately)"""
    filename: str = Field(..., min_length=1, max_length=255)
    file_type: str = Field(..., description="pdf, txt, md, docx")
    doc_metadata: dict = Field(default_factory=dict)


class DocumentUpload(BaseModel):
    """Schema for document upload with file content"""
    filename: str = Field(..., min_length=1, max_length=255)
    content: str = Field(..., description="Base64 encoded file content or raw text")
    file_type: str = Field(..., description="pdf, txt, md, docx")
    doc_metadata: dict = Field(default_factory=dict)


class DocumentUpdate(BaseModel):
    """Schema for updating document metadata"""
    filename: Optional[str] = None
    doc_metadata: Optional[dict] = None
    is_active: Optional[bool] = None


class DocumentResponse(BaseModel):
    """Schema for document response"""
    id: UUID
    organization_id: UUID
    filename: str
    file_path: str
    file_type: str
    file_size: int
    doc_metadata: dict
    is_active: bool
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class DocumentListResponse(BaseModel):
    """Response for listing documents"""
    documents: List[DocumentResponse]
    total: int


class DocumentReingestRequest(BaseModel):
    """Request to re-ingest a document"""
    chunk_size: int = Field(default=1000, ge=100, le=2000)
    overlap: int = Field(default=200, ge=0, le=500)