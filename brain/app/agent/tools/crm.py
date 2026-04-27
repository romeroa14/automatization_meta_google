"""CRM tools for LangGraph agent"""
from typing import Optional, List, Dict, Any
from langchain_core.tools import tool
from app.core.config import settings
import httpx


class CRMClient:
    """Client for Laravel CRM API"""

    def __init__(self):
        self.base_url = settings.laravel_api_url
        self.api_key = settings.laravel_api_key

    async def search_customer(
        self, phone: Optional[str] = None, name: Optional[str] = None
    ) -> Dict[str, Any]:
        """Search for a customer by phone or name"""
        async with httpx.AsyncClient() as client:
            params = {}
            if phone:
                params["phone"] = phone
            if name:
                params["name"] = name

            response = await client.get(
                f"{self.base_url}/customers/search",
                params=params,
                headers={"Authorization": f"Bearer {self.api_key}"},
            )

            if response.status_code == 200:
                return response.json()
            return {"error": "Customer not found", "results": []}

    async def update_lead(
        self, lead_id: int, status: str, notes: Optional[str] = None
    ) -> Dict[str, Any]:
        """Update a lead's status"""
        async with httpx.AsyncClient() as client:
            response = await client.put(
                f"{self.base_url}/leads/{lead_id}",
                json={"status": status, "notes": notes},
                headers={"Authorization": f"Bearer {self.api_key}"},
            )

            if response.status_code == 200:
                return response.json()
            return {"error": "Failed to update lead"}

    async def create_deal(
        self, customer_id: int, product_id: str, amount: float, notes: Optional[str] = None
    ) -> Dict[str, Any]:
        """Create a new deal/opportunity"""
        async with httpx.AsyncClient() as client:
            response = await client.post(
                f"{self.base_url}/deals",
                json={
                    "customer_id": customer_id,
                    "product_id": product_id,
                    "amount": amount,
                    "notes": notes,
                },
                headers={"Authorization": f"Bearer {self.api_key}"},
            )

            if response.status_code in (200, 201):
                return response.json()
            return {"error": "Failed to create deal"}


# Singleton client
_crm_client = None


def get_crm_client() -> CRMClient:
    """Get or create CRM client"""
    global _crm_client
    if _crm_client is None:
        _crm_client = CRMClient()
    return _crm_client


@tool
async def search_customer(phone: str = None, name: str = None) -> dict:
    """
    Search for a customer in the CRM by phone number or name.
    
    Args:
        phone: Customer phone number (e.g., +58 412 1234567)
        name: Customer name (full or partial)
    
    Returns:
        List of matching customers with their details
    """
    client = get_crm_client()
    return await client.search_customer(phone=phone, name=name)


@tool
async def update_lead(lead_id: int, status: str, notes: str = None) -> dict:
    """
    Update a lead's status in the CRM.
    
    Args:
        lead_id: The lead ID to update
        status: New status (new, contacted, qualified, proposal, negotiation, won, lost)
        notes: Optional notes about the status change
    
    Returns:
        Updated lead details
    """
    client = get_crm_client()
    return await client.update_lead(lead_id=lead_id, status=status, notes=notes)


@tool
async def create_deal(customer_id: int, product_id: str, amount: float, notes: str = None) -> dict:
    """
    Create a new deal/opportunity in the CRM.
    
    Args:
        customer_id: The customer ID
        product_id: The product/service ID
        amount: Deal amount
        notes: Optional notes
    
    Returns:
        Created deal details
    """
    client = get_crm_client()
    return await client.create_deal(
        customer_id=customer_id,
        product_id=product_id,
        amount=amount,
        notes=notes,
    )


@tool
async def get_products(organization_id: str = None, limit: int = 50) -> List[dict]:
    """
    Get the product catalog for the specified organization.
    
    Args:
        organization_id: The UUID of the organization (required)
        limit: Maximum number of products to return (default: 50)
    
    Returns:
        List of available products with details
    """
    from uuid import UUID
    from app.services.product_service import ProductService
    from app.schemas.products import ProductFilters

    if not organization_id:
        return {"error": "organization_id is required"}

    try:
        org_uuid = UUID(organization_id)
    except ValueError:
        return {"error": "Invalid organization_id format"}

    try:
        service = ProductService(organization_id=org_uuid)
        filters = ProductFilters(limit=limit, offset=0)
        products = await service.list(filters)
        
        return {
            "products": [
                {
                    "id": str(p.id),
                    "name": p.name,
                    "description": p.description,
                    "price": p.price,
                    "category": p.category,
                    "sku": p.sku,
                    "image_url": p.image_url,
                    "is_active": p.is_active,
                }
                for p in products
            ],
            "count": len(products),
        }
    except Exception as e:
        return {"error": str(e)}


@tool
async def search_products(
    query: str,
    organization_id: str = None,
    search_type: str = "hybrid",
    limit: int = 10
) -> List[dict]:
    """
    Search for products using hybrid search (combines literal and semantic).
    
    Args:
        query: Search query string (required)
        organization_id: The UUID of the organization (required)
        search_type: Type of search - 'literal', 'semantic', or 'hybrid' (default: 'hybrid')
        limit: Maximum results to return (default: 10)
    
    Returns:
        List of matching products with relevance scores
    """
    from uuid import UUID
    from app.services.product_service import ProductService

    if not organization_id:
        return {"error": "organization_id is required"}
    
    if not query:
        return {"error": "query is required"}

    try:
        org_uuid = UUID(organization_id)
    except ValueError:
        return {"error": "Invalid organization_id format"}

    if search_type not in ("literal", "semantic", "hybrid"):
        return {"error": "search_type must be 'literal', 'semantic', or 'hybrid'"}

    try:
        service = ProductService(organization_id=org_uuid)
        
        if search_type == "literal":
            results = await service.search_literal(query, limit=limit)
            return {
                "products": [
                    {
                        "id": str(p.id),
                        "name": p.name,
                        "description": p.description,
                        "price": p.price,
                        "category": p.category,
                        "sku": p.sku,
                        "score": 1.0,
                        "match_type": "literal",
                    }
                    for p in results
                ],
                "count": len(results),
                "search_type": "literal",
            }
        elif search_type == "semantic":
            results = await service.search_semantic(query, limit=limit)
            return {
                "products": [
                    {
                        "id": str(r.product.id),
                        "name": r.product.name,
                        "description": r.product.description,
                        "price": r.product.price,
                        "category": r.product.category,
                        "sku": r.product.sku,
                        "score": r.score,
                        "match_type": "semantic",
                    }
                    for r in results
                ],
                "count": len(results),
                "search_type": "semantic",
            }
        else:  # hybrid
            results = await service.search_hybrid(query, limit=limit)
            return {
                "products": [
                    {
                        "id": str(r.product.id),
                        "name": r.product.name,
                        "description": r.product.description,
                        "price": r.product.price,
                        "category": r.product.category,
                        "sku": r.product.sku,
                        "score": r.score,
                        "match_type": r.match_type,
                    }
                    for r in results
                ],
                "count": len(results),
                "search_type": "hybrid",
            }
    except Exception as e:
        return {"error": str(e)}