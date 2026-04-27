from fastapi import FastAPI
from app.schemas.chat import ChatRequest
from app.agent.graph import get_compiled_graph
from app.api import (
    products_router,
    search_router,
    documents_router,
    rag_router,
    webhook_router,
)
from app.core.db import init_db

app = FastAPI(
    title="Admetricas Brain API",
    description="Python LangGraph Agent Engine + RAG Microservice",
    version="0.2.0",
)

# Include API routers
app.include_router(products_router, prefix="/api/v1")
app.include_router(search_router, prefix="/api/v1")
app.include_router(documents_router, prefix="/api/v1")
app.include_router(rag_router, prefix="/api/v1")
app.include_router(webhook_router, prefix="/api")

# Legacy chat endpoint
@app.post("/api/v1/chat")
async def chat_endpoint(request: ChatRequest):
    thread_id = f"{request.organization_id}:{request.platform}:{request.customer_id}"
    config = {"configurable": {"thread_id": thread_id}}
    
    state = {
        "messages": [("user", request.message)],
        "organization_id": request.organization_id,
        "whatsapp_phone_number_id": request.whatsapp_phone_number_id,
        "customer_id": request.customer_id,
        "platform": request.platform,
    }
    
    async with get_compiled_graph() as agent_app:
        result = await agent_app.ainvoke(state, config)
        
    messages = result.get("messages", [])
    if messages:
        last_message = messages[-1]
        response_text = last_message.content if hasattr(last_message, "content") else last_message[1] if isinstance(last_message, tuple) else str(last_message)
    else:
        response_text = "No response"
        
    return {"response": response_text}


@app.get("/health")
async def health_check():
    return {"status": "ok"}


@app.on_event("startup")
async def startup():
    # Initialize database extensions
    await init_db()