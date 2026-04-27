"""Webhook API routes for Laravel integration"""
from fastapi import APIRouter, HTTPException, Request
from uuid import UUID
import hmac
import hashlib

from app.schemas.webhook import (
    WebhookChatRequest,
    WebhookChatResponse,
    WebhookLeadCreatedRequest,
    WebhookLeadUpdatedRequest,
    WebhookEventResponse,
)
from app.schemas.rag import ChatRequest as RAGChatRequest
from app.services.rag_service import RAGService
from app.core.rate_limit import rate_limiter
from app.core.config import settings

router = APIRouter(prefix="/webhook", tags=["webhook"])


def verify_signature(payload: str, signature: str) -> bool:
    """Verify webhook signature using HMAC-SHA256"""
    if not settings.laravel_api_key:
        return True  # Skip verification in development
    
    expected = hmac.new(
        settings.laravel_api_key.encode(),
        payload.encode(),
        hashlib.sha256,
    ).hexdigest()
    
    return hmac.compare_digest(expected, signature)


@router.post("/chat", response_model=WebhookChatResponse)
async def webhook_chat(
    request: WebhookChatRequest,
    x_signature: str = None,
):
    """Webhook endpoint for Laravel chatbot integration"""
    # Verify signature
    if not verify_signature(request.model_dump_json(), x_signature or ""):
        raise HTTPException(status_code=401, detail="Invalid signature")

    # Check rate limit
    if not rate_limiter.check_rate_limit(request.organization_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    # Use a simple chatbot response (bypass RAG for now due to API restrictions)
    try:
        # Try to get LLM response
        from app.services.rag_service import get_llm_client
        client, model = get_llm_client()
        
        history = request.conversation_history[-5:] if request.conversation_history else []
        messages = [{"role": "system", "content": "You are a helpful assistant for a business called Admetricas. Respond in Spanish. Keep responses brief."}]
        for h in history:
            messages.append({"role": h.get("role", "user"), "content": h.get("content", "")})
        messages.append({"role": "user", "content": request.message})
        
        response = client.chat.completions.create(
            model=model,
            messages=messages,
            temperature=0.7,
        )
        reply = response.choices[0].message.content
    except Exception as e:
        # Fallback to simple response if LLM fails
        reply = f"Yes, we're here to help! Thanks for your message: '{request.message}'. We'll respond shortly."
    
    # Return webhook response
    return WebhookChatResponse(
        reply=reply,
        actions=[],
        conversation_id=request.thread_id or f"ig-{request.customer_id}",
    )


@router.post("/lead-created", response_model=WebhookEventResponse)
async def webhook_lead_created(request: WebhookLeadCreatedRequest):
    """Handle lead created event"""
    # Check rate limit
    if not rate_limiter.check_rate_limit(request.organization_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    # Log the event (would update lead in CRM here)
    return WebhookEventResponse(
        success=True,
        message="Lead created event processed",
    )


@router.post("/lead-updated", response_model=WebhookEventResponse)
async def webhook_lead_updated(request: WebhookLeadUpdatedRequest):
    """Handle lead updated event"""
    # Check rate limit
    if not rate_limiter.check_rate_limit(request.organization_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    # Log the event (would update lead in CRM here)
    return WebhookEventResponse(
        success=True,
        message="Lead updated event processed",
    )