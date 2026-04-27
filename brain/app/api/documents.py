"""Document API routes"""
from typing import List
from uuid import UUID
from fastapi import APIRouter, HTTPException, UploadFile, File, Form
from fastapi.responses import JSONResponse

from app.schemas.documents import (
    DocumentCreate,
    DocumentResponse,
    DocumentListResponse,
    DocumentReingestRequest,
)
from app.services.document_service import DocumentService
from app.core.rate_limit import rate_limiter

router = APIRouter(prefix="/organizations/{org_id}/documents", tags=["documents"])


def get_org_id(org_id: str) -> UUID:
    """Convert org_id string to UUID"""
    try:
        return UUID(org_id)
    except ValueError:
        raise HTTPException(status_code=400, detail="Invalid organization ID format")


@router.post("", response_model=DocumentResponse, status_code=201)
async def upload_document(
    org_id: str,
    file: UploadFile = File(...),
    file_type: str = Form(...),
):
    """Upload a document"""
    org_uuid = get_org_id(org_id)

    # Check rate limit
    if not rate_limiter.check_rate_limit(org_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    # Validate file type
    allowed_types = ["pdf", "txt", "md", "docx"]
    if file_type not in allowed_types:
        raise HTTPException(
            status_code=400,
            detail=f"File type must be one of: {', '.join(allowed_types)}",
        )

    # Read file content
    content = await file.read()

    service = DocumentService(org_uuid)
    return await service.upload(
        DocumentCreate(
            filename=file.filename,
            file_type=file_type,
            doc_metadata={},
        ),
        content,
    )


@router.get("", response_model=DocumentListResponse)
async def list_documents(org_id: str):
    """List all documents"""
    org_uuid = get_org_id(org_id)

    # Check rate limit
    if not rate_limiter.check_rate_limit(org_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    service = DocumentService(org_uuid)
    return await service.list()


@router.get("/{document_id}", response_model=DocumentResponse)
async def get_document(
    org_id: str,
    document_id: str,
):
    """Get a document by ID"""
    org_uuid = get_org_id(org_id)
    doc_uuid = get_org_id(document_id)

    service = DocumentService(org_uuid)
    doc = await service.get(doc_uuid)

    if not doc:
        raise HTTPException(status_code=404, detail="Document not found")

    return doc


@router.delete("/{document_id}", status_code=204)
async def delete_document(
    org_id: str,
    document_id: str,
):
    """Delete a document"""
    org_uuid = get_org_id(org_id)
    doc_uuid = get_org_id(document_id)

    # Check rate limit
    if not rate_limiter.check_rate_limit(org_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    service = DocumentService(org_uuid)
    deleted = await service.delete(doc_uuid)

    if not deleted:
        raise HTTPException(status_code=404, detail="Document not found")

    return JSONResponse(content=None, status_code=204)


@router.post("/{document_id}/reingest", status_code=202)
async def reingest_document(
    org_id: str,
    document_id: str,
    request: DocumentReingestRequest = DocumentReingestRequest(),
):
    """Re-ingest a document with new parameters"""
    org_uuid = get_org_id(org_id)
    doc_uuid = get_org_id(document_id)

    # Check rate limit
    if not rate_limiter.check_rate_limit(org_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    service = DocumentService(org_uuid)
    success = await service.reingest(doc_uuid, request)

    if not success:
        raise HTTPException(status_code=404, detail="Document not found")

    return {"status": "reingestion_started", "document_id": document_id}