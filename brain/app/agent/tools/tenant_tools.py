"""Tenant-specific tools for LangGraph agent"""
from typing import Optional, List, Dict, Any
from langchain_core.tools import tool
import httpx
import os


class TenantClient:
    """Client for tenant-specific data access"""

    def __init__(self):
        self.laravel_url = os.getenv("LARAVEL_URL", "http://localhost")

    async def get_conversation_history(
        self, tenant_id: str, customer_id: str, limit: int = 20
    ) -> Dict[str, Any]:
        """Get all messages for a specific customer"""
        async with httpx.AsyncClient(timeout=30.0) as client:
            try:
                response = await client.get(
                    f"{self.laravel_url}/api/v1/tenant/{tenant_id}/conversations/{customer_id}/messages",
                    params={"limit": limit},
                )
                if response.status_code == 200:
                    return response.json()
                return {"error": f"Status {response.status_code}", "messages": []}
            except Exception as e:
                return {"error": str(e), "messages": []}

    async def get_leads(
        self, tenant_id: str, status: Optional[str] = None, limit: int = 50
    ) -> Dict[str, Any]:
        """Get leads for a tenant with optional status filter"""
        async with httpx.AsyncClient(timeout=30.0) as client:
            try:
                params = {"limit": limit}
                if status:
                    params["status"] = status
                response = await client.get(
                    f"{self.laravel_url}/api/v1/tenant/{tenant_id}/leads",
                    params=params,
                )
                if response.status_code == 200:
                    return response.json()
                return {"error": f"Status {response.status_code}", "leads": []}
            except Exception as e:
                return {"error": str(e), "leads": []}

    async def get_services(self, tenant_id: str) -> Dict[str, Any]:
        """Get available services for a tenant"""
        async with httpx.AsyncClient(timeout=30.0) as client:
            try:
                response = await client.get(
                    f"{self.laravel_url}/api/v1/tenant/{tenant_id}/services",
                )
                if response.status_code == 200:
                    return response.json()
                return {"error": f"Status {response.status_code}", "services": []}
            except Exception as e:
                return {"error": str(e), "services": []}

    async def search_knowledge(
        self, tenant_id: str, query: str, limit: int = 5
    ) -> Dict[str, Any]:
        """Search in tenant's knowledge base (RAG)"""
        async with httpx.AsyncClient(timeout=30.0) as client:
            try:
                response = await client.post(
                    f"{self.laravel_url}/api/v1/tenant/{tenant_id}/rag/query",
                    json={"query": query, "limit": limit},
                )
                if response.status_code == 200:
                    return response.json()
                return {"error": f"Status {response.status_code}", "results": []}
            except Exception as e:
                return {"error": str(e), "results": []}

    async def get_conversations_summary(
        self, tenant_id: str, days: int = 7
    ) -> Dict[str, Any]:
        """Get conversation stats for a tenant"""
        async with httpx.AsyncClient(timeout=30.0) as client:
            try:
                response = await client.get(
                    f"{self.laravel_url}/api/v1/tenant/{tenant_id}/stats/conversations",
                    params={"days": days},
                )
                if response.status_code == 200:
                    return response.json()
                return {"error": f"Status {response.status_code}", "stats": {}}
            except Exception as e:
                return {"error": str(e), "stats": {}}


# Singleton
_tenant_client = None


def get_tenant_client() -> TenantClient:
    global _tenant_client
    if _tenant_client is None:
        _tenant_client = TenantClient()
    return _tenant_client


@tool
async def get_conversation_history(tenant_id: str, customer_id: str, limit: int = 20) -> dict:
    """
    Get all messages exchanged with a specific customer.
    
    Args:
        tenant_id: The tenant UUID (e.g., 'ads_vnzla' tenant ID)
        customer_id: The customer/platform user ID
        limit: Maximum messages to return (default: 20)
    
    Returns:
        Dictionary with conversation messages and metadata
    """
    client = get_tenant_client()
    return await client.get_conversation_history(tenant_id, customer_id, limit)


@tool
async def get_leads(tenant_id: str, status: str = None, limit: int = 50) -> dict:
    """
    Get leads for a tenant, optionally filtered by status.
    
    Args:
        tenant_id: The tenant UUID
        status: Filter by lead status - 'nuevo', 'contactado', 'interesado', 'cliente' (optional)
        limit: Maximum leads to return (default: 50)
    
    Returns:
        List of leads with their details
    """
    client = get_tenant_client()
    return await client.get_leads(tenant_id, status, limit)


@tool
async def get_services(tenant_id: str) -> dict:
    """
    Get available services/products for a tenant.
    
    Args:
        tenant_id: The tenant UUID
    
    Returns:
        List of services with pricing and details
    """
    client = get_tenant_client()
    return await client.get_services(tenant_id)


@tool
async def search_knowledge(tenant_id: str, query: str, limit: int = 5) -> dict:
    """
    Search in the tenant's knowledge base / documentation.
    
    Args:
        tenant_id: The tenant UUID
        query: Search query
        limit: Maximum results (default: 5)
    
    Returns:
        Relevant document chunks with sources
    """
    client = get_tenant_client()
    return await client.search_knowledge(tenant_id, query, limit)


@tool
async def get_conversations_summary(tenant_id: str, days: int = 7) -> dict:
    """
    Get conversation statistics for a tenant.
    
    Args:
        tenant_id: The tenant UUID
        days: Number of days to look back (default: 7)
    
    Returns:
        Stats: total conversations, active, messages today, etc.
    """
    client = get_tenant_client()
    return await client.get_conversations_summary(tenant_id, days)