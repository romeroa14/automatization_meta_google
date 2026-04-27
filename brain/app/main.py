"""Brain Service - Admetricas AI Agent with DeepSeek V4"""

from fastapi import FastAPI, HTTPException
from fastapi.responses import StreamingResponse
from pydantic import BaseModel
from typing import List, Optional, Dict, Any, Iterator
import os
import asyncio

# Load environment
from dotenv import load_dotenv
load_dotenv()

from langchain_nvidia_ai_endpoints import ChatNVIDIA
from langchain_core.messages import HumanMessage, SystemMessage, AIMessage

app = FastAPI(title="Admetricas Brain API", version="0.2.0")

# NVIDIA API Key for DeepSeek V4
NVIDIA_API_KEY = os.getenv("NVIDIA_API_KEY", "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")


class ChatMessage(BaseModel):
    role: str
    content: str


class WebhookChatRequest(BaseModel):
    organization_id: str = "default"
    platform: str = "instagram"
    customer_id: str
    customer_phone: Optional[str] = None
    message: str
    thread_id: Optional[str] = None
    conversation_history: List[Dict[str, str]] = []


class WebhookChatResponse(BaseModel):
    reply: str
    reasoning: Optional[str] = None
    actions: List[str] = []
    conversation_id: str


def get_deepseek_client() -> ChatNVIDIA:
    """Get DeepSeek V4 client via NVIDIA"""
    return ChatNVIDIA(
        model="deepseek-ai/deepseek-v4-flash",
        api_key=NVIDIA_API_KEY,
        temperature=1,
        top_p=0.95,
        max_completion_tokens=16384,
        model_kwargs={
            "chat_template_kwargs": {
                "thinking": True,
                "reasoning_effort": "high"
            }
        }
    )


@app.get("/")
def root():
    return {"status": "ok", "service": "admetricas-brain", "model": "deepseek-v4-flash"}


@app.get("/health")
def health():
    return {"status": "healthy", "model": "deepseek-v4-flash"}


def format_conversation_context(org_id: str, platform: str, history: List[Dict]) -> str:
    """Build context string from conversation history"""
    context_parts = [
        f"Organización: {org_id}",
        f"Plataforma: {platform}",
    ]

    if history:
        context_parts.append("Conversación reciente:")
        for msg in history[-10:]:  # Last 10 messages
            role = msg.get("role", "user")
            content = msg.get("content", "")
            context_parts.append(f"- {role}: {content}")

    return "\n".join(context_parts)


async def generate_response_stream(
    messages: List[Dict],
    organization_id: str,
    platform: str
) -> Iterator[Dict]:
    """Generate streaming response with reasoning"""
    client = get_deepseek_client()

    # Build LangChain messages
    lc_messages = []
    for msg in messages:
        role = msg.get("role", "user")
        content = msg.get("content", "")
        if role == "system":
            lc_messages.append(SystemMessage(content=content))
        elif role == "assistant":
            lc_messages.append(AIMessage(content=content))
        else:
            lc_messages.append(HumanMessage(content=content))

    # Stream chunks
    reasoning_buffer = ""
    response_buffer = ""

    try:
        for chunk in client.stream(lc_messages):
            # Extract reasoning if present
            reasoning = None
            if hasattr(chunk, 'additional_kwargs') and chunk.additional_kwargs:
                reasoning = chunk.additional_kwargs.get("reasoning") or \
                            chunk.additional_kwargs.get("reasoning_content")

            if reasoning:
                reasoning_buffer += reasoning
                yield {"type": "reasoning", "content": reasoning}

            if chunk.content:
                response_buffer += chunk.content
                yield {"type": "content", "content": chunk.content}

    except Exception as e:
        yield {"type": "error", "content": str(e)}


@app.post("/api/webhook/chat", response_model=WebhookChatResponse)
async def webhook_chat(request: WebhookChatRequest):
    """Webhook endpoint for Laravel chatbot integration"""

    # Build context
    context = format_conversation_context(
        request.organization_id,
        request.platform,
        request.conversation_history
    )

    # Build messages for DeepSeek
    messages = [
        {
            "role": "system",
            "content": f"""Eres un asistente virtual amigable para una empresa llamada Admetricas.
Responde en español, de manera breve y útil.
Contexto actual:
{context}

Instrucciones:
- Sé amigable y profesional
- Responde preguntas sobre productos/servicios
- Si no sabes algo, ofrece contactar a un humano
- Mantén las respuestas cortas (máximo 3-4 oraciones)"""
        }
    ]

    # Add conversation history
    for h in request.conversation_history[-10:]:
        messages.append({
            "role": h.get("role", "user"),
            "content": h.get("content", "")
        })

    # Add current message
    messages.append({"role": "user", "content": request.message})

    # Call DeepSeek V4
    client = get_deepseek_client()
    reasoning_content = ""

    try:
        # Collect full response
        full_response = ""
        for chunk in client.stream(messages):
            reasoning = None
            if hasattr(chunk, 'additional_kwargs') and chunk.additional_kwargs:
                reasoning = chunk.additional_kwargs.get("reasoning") or \
                            chunk.additional_kwargs.get("reasoning_content")

            if reasoning:
                reasoning_content += reasoning + "\n"

            if chunk.content:
                full_response += chunk.content

        reply = full_response if full_response else "Gracias por tu mensaje. Un asesor se comunicará contigo pronto."

    except Exception as e:
        reply = f"Gracias por tu mensaje. Lo procesaremos pronto."

    return WebhookChatResponse(
        reply=reply,
        reasoning=reasoning_content if reasoning_content else None,
        actions=[],
        conversation_id=request.thread_id or f"ig-{request.customer_id}"
    )


@app.post("/api/webhook/chat/stream")
async def webhook_chat_stream(request: WebhookChatRequest):
    """Streaming version of webhook chat"""

    # Build messages
    messages = [
        {
            "role": "system",
            "content": "Eres un asistente amigable para Admetricas. Responde en español de manera breve."
        }
    ]

    for h in request.conversation_history[-10:]:
        messages.append({
            "role": h.get("role", "user"),
            "content": h.get("content", "")
        })

    messages.append({"role": "user", "content": request.message})

    async def event_generator():
        client = get_deepseek_client()

        for chunk in client.stream(messages):
            reasoning = None
            if hasattr(chunk, 'additional_kwargs') and chunk.additional_kwargs:
                reasoning = chunk.additional_kwargs.get("reasoning") or \
                            chunk.additional_kwargs.get("reasoning_content")

            if reasoning:
                yield f"data: {{\"type\": \"reasoning\", \"content\": {repr(reasoning)}}}\n\n"

            if chunk.content:
                yield f"data: {{\"type\": \"content\", \"content\": {repr(chunk.content)}}}\n\n"

    return StreamingResponse(
        event_generator(),
        media_type="text/event-stream",
        headers={
            "Cache-Control": "no-cache",
            "Connection": "keep-alive",
        }
    )


# Legacy endpoint (same as webhook)
@app.post("/webhook/chat", response_model=WebhookChatResponse)
async def legacy_webhook_chat(request: WebhookChatRequest):
    """Legacy webhook endpoint"""
    return await webhook_chat(request)


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)