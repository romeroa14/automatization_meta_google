"""Product search API routes"""
from fastapi import APIRouter, HTTPException, Query
from app.schemas.products import ProductSearchRequest, ProductSearchResponse
from app.services.product_service import ProductService
from app.core.rate_limit import rate_limiter
from uuid import UUID
from typing import List

router = APIRouter(prefix="/organizations/{org_id}/search", tags=["search"])


def get_org_id(org_id: str) -> UUID:
    """Convert org_id string to UUID"""
    try:
        return UUID(org_id)
    except ValueError:
        raise HTTPException(status_code=400, detail="Invalid organization ID format")


@router.post("/products", response_model=ProductSearchResponse)
async def search_products(
    org_id: str,
    request: ProductSearchRequest,
):
    """Hybrid search for products"""
    org_uuid = get_org_id(org_id)

    # Check rate limit
    if not rate_limiter.check_rate_limit(org_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    service = ProductService(org_uuid)

    if request.search_type == "literal":
        results = await service.search_literal(request.query, limit=request.limit)
        # Convert to format
        from app.schemas.products import ProductSearchResult
        search_results = [
            ProductSearchResult(product=p, score=1.0, match_type="literal")
            for p in results
        ]
    elif request.search_type == "semantic":
        search_results = await service.search_semantic(request.query, limit=request.limit)
    else:  # hybrid
        search_results = await service.search_hybrid(request.query, limit=request.limit)

    return ProductSearchResponse(
        results=search_results,
        total=len(search_results),
        query=request.query,
    )