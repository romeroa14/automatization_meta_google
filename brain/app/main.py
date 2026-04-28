"""Brain Service - Admetricas AI Agent with DeepSeek"""

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Optional, Dict, Any
import os
import httpx

from dotenv import load_dotenv
load_dotenv()

app = FastAPI(title="Admetricas Brain API", version="0.2.0")

# DeepSeek API (old working version)
DEEPSEEK_API_KEY = os.getenv("DEEPSEEK_API_KEY", "sk-6ab5dbfd2627446181e8e40d77550914")
DEEPSEEK_BASE_URL = os.getenv("DEEPSEEK_BASE_URL", "https://api.deepseek.com")


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
    actions: List[str] = []
    conversation_id: str


@app.get("/")
def root():
    return {"status": "ok", "service": "admetricas-brain", "model": "deepseek-chat"}


@app.get("/health")
def health():
    return {"status": "healthy", "model": "deepseek-chat"}


@app.post("/api/webhook/chat", response_model=WebhookChatResponse)
async def webhook_chat(request: WebhookChatRequest):
    """Webhook endpoint for Laravel chatbot integration"""

    # Build messages for DeepSeek
    messages = [
        {"role": "system", "content": "You are a helpful assistant for a business called Admetricas. Respond in Spanish, keep responses brief and friendly."}
    ]

    # Add conversation history
    for h in request.conversation_history[-5:]:
        messages.append({"role": h.get("role", "user"), "content": h.get("content", "")})

    # Add current message
    messages.append({"role": "user", "content": request.message})

    # Call DeepSeek API
    reply = "Gracias por tu mensaje. Un asesor se comunicará contigo pronto."
    if DEEPSEEK_API_KEY:
        try:
            async with httpx.AsyncClient(timeout=60.0) as client:
                response = await client.post(
                    f"{DEEPSEEK_BASE_URL}/v1/chat/completions",
                    json={
                        "model": "deepseek-chat",
                        "messages": messages,
                        "temperature": 0.7,
                        "max_tokens": 500,
                    },
                    headers={
                        "Authorization": f"Bearer {DEEPSEEK_API_KEY}",
                        "Content-Type": "application/json"
                    }
                )
                if response.status_code == 200:
                    data = response.json()
                    reply = data["choices"][0]["message"]["content"]
                else:
                    reply = f"Error: {response.status_code}"
        except Exception as e:
            reply = "Gracias por tu mensaje. Lo procesaremos pronto."

    return WebhookChatResponse(
        reply=reply,
        actions=[],
        conversation_id=request.thread_id or f"ig-{request.customer_id}"
    )


@app.post("/webhook/chat", response_model=WebhookChatResponse)
async def legacy_webhook_chat(request: WebhookChatRequest):
    """Legacy webhook endpoint"""
    return await webhook_chat(request)


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)