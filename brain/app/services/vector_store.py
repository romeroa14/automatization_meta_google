from typing import Any, Dict, List, Optional
from langchain_postgres.vectorstores import PGVector
from langchain_core.embeddings import Embeddings
from langchain_openai import OpenAIEmbeddings
import os

DATABASE_URL = os.getenv("DATABASE_URL", "postgresql+psycopg://postgres:postgres@localhost:5432/postgres")

class OrganizationPGVector(PGVector):
    def __init__(self, organization_id: str, *args, **kwargs):
        if not organization_id:
            raise ValueError("organization_id is required")
        self.organization_id = organization_id
        super().__init__(*args, **kwargs)

    async def asearch(self, query: str, k: int = 4, filter: Optional[Dict[str, Any]] = None, **kwargs) -> List[Any]:
        if filter is None:
            filter = {}
        filter["organization_id"] = self.organization_id
        return await super().asearch(query, k=k, filter=filter, **kwargs)

async def get_vector_store(organization_id: str) -> PGVector:
    if not organization_id:
        raise ValueError("organization_id is required")
    
    embeddings = OpenAIEmbeddings()
    
    store = PGVector(
        embeddings=embeddings,
        collection_name="documents",
        connection=DATABASE_URL,
        use_jsonb=True,
    )
    # Mocking a filter property for testing purposes as langchain's PGVector might not expose it directly in this way
    # Or we can just use the wrapper
    
    # Actually, let's return a wrapper that injects the filter
    class VectorStoreWrapper:
        def __init__(self, store, org_id):
            self.store = store
            self.filter = {"organization_id": org_id}
            
        async def asearch(self, query, **kwargs):
            return await self.store.asearch(query, filter=self.filter, **kwargs)
            
    return VectorStoreWrapper(store, organization_id)
