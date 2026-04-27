# brain/app/schemas/__init__.py
# Pydantic schemas exports

from app.schemas.products import (
    ProductCreate,
    ProductUpdate,
    ProductResponse,
    ProductFilters,
    ProductSearchRequest,
    ProductSearchResponse,
)
from app.schemas.documents import (
    DocumentCreate,
    DocumentUpload,
    DocumentUpdate,
    DocumentResponse,
    DocumentListResponse,
    DocumentReingestRequest,
)
from app.schemas.rag import (
    RAGQueryRequest,
    RAGQueryResponse,
    ChatRequest as RAGChatRequest,
    ChatResponse as RAGChatResponse,
    RAGHistoryRequest,
    RAGHistoryResponse,
    ChatMessage,
    SourceDocument,
)
from app.schemas.webhook import (
    WebhookChatRequest,
    WebhookChatResponse,
    WebhookLeadCreatedRequest,
    WebhookLeadUpdatedRequest,
    WebhookEventResponse,
    Action,
)
from app.schemas.chat import ChatRequest