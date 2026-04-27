"""Product API routes"""
from typing import List
from uuid import UUID
from fastapi import APIRouter, HTTPException, Depends, Query
from fastapi.responses import JSONResponse

from app.schemas.products import (
    ProductCreate,
    ProductUpdate,
    ProductResponse,
    ProductFilters,
    ProductSearchRequest,
    ProductSearchResponse,
)
from app.services.product_service import ProductService
from app.core.rate_limit import rate_limiter

router = APIRouter(prefix="/organizations/{org_id}/products", tags=["products"])


def get_org_id(org_id: str) -> UUID:
    """Convert org_id string to UUID"""
    try:
        return UUID(org_id)
    except ValueError:
        raise HTTPException(status_code=400, detail="Invalid organization ID format")


@router.post("", response_model=ProductResponse, status_code=201)
async def create_product(
    org_id: str,
    product: ProductCreate,
):
    """Create a new product"""
    org_uuid = get_org_id(org_id)

    # Check rate limit
    if not rate_limiter.check_rate_limit(org_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    service = ProductService(org_uuid)
    return await service.create(product)


@router.get("", response_model=List[ProductResponse])
async def list_products(
    org_id: str,
    category: str = Query(None),
    is_active: bool = Query(True),
    min_price: float = Query(None, ge=0),
    max_price: float = Query(None, ge=0),
    search: str = Query(None),
    limit: int = Query(50, ge=1, le=100),
    offset: int = Query(0, ge=0),
):
    """List products with filters"""
    org_uuid = get_org_id(org_id)

    # Check rate limit
    if not rate_limiter.check_rate_limit(org_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    filters = ProductFilters(
        category=category,
        is_active=is_active,
        min_price=min_price,
        max_price=max_price,
        search=search,
        limit=limit,
        offset=offset,
    )

    service = ProductService(org_uuid)
    return await service.list(filters)


@router.get("/{product_id}", response_model=ProductResponse)
async def get_product(
    org_id: str,
    product_id: str,
):
    """Get a product by ID"""
    org_uuid = get_org_id(org_id)
    product_uuid = get_org_id(product_id)

    service = ProductService(org_uuid)
    product = await service.get(product_uuid)

    if not product:
        raise HTTPException(status_code=404, detail="Product not found")

    return product


@router.put("/{product_id}", response_model=ProductResponse)
async def update_product(
    org_id: str,
    product_id: str,
    product: ProductUpdate,
):
    """Update a product"""
    org_uuid = get_org_id(org_id)
    product_uuid = get_org_id(product_id)

    # Check rate limit
    if not rate_limiter.check_rate_limit(org_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    service = ProductService(org_uuid)
    updated = await service.update(product_uuid, product)

    if not updated:
        raise HTTPException(status_code=404, detail="Product not found")

    return updated


@router.delete("/{product_id}", status_code=204)
async def delete_product(
    org_id: str,
    product_id: str,
):
    """Delete a product"""
    org_uuid = get_org_id(org_id)
    product_uuid = get_org_id(product_id)

    # Check rate limit
    if not rate_limiter.check_rate_limit(org_id):
        raise HTTPException(status_code=429, detail="Rate limit exceeded")

    service = ProductService(org_uuid)
    deleted = await service.delete(product_uuid)

    if not deleted:
        raise HTTPException(status_code=404, detail="Product not found")

    return JSONResponse(content=None, status_code=204)