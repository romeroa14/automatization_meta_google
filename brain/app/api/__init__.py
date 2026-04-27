# brain/app/api/__init__.py
# API routes exports

from app.api.products import router as products_router
from app.api.search import router as search_router
from app.api.documents import router as documents_router
from app.api.rag import router as rag_router
from app.api.webhook import router as webhook_router