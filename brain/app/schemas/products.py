from pydantic import BaseModel, Field
from uuid import UUID
from datetime import datetime
from typing import Optional, List
from decimal import Decimal


class ProductBase(BaseModel):
    """Base fields for product - not used directly for requests"""
    name: str = Field(..., min_length=1, max_length=255, description="Product name")
    description: Optional[str] = None
    price: Optional[float] = Field(None, ge=0, description="Product price")
    category: Optional[str] = Field(None, max_length=100)
    sku: Optional[str] = Field(None, max_length=100)
    image_url: Optional[str] = None
    metadata: dict = Field(default_factory=dict)


class ProductCreate(ProductBase):
    """Schema for creating a new product"""
    pass


class ProductUpdate(BaseModel):
    """Schema for updating an existing product - all fields optional"""
    name: Optional[str] = Field(None, min_length=1, max_length=255)
    description: Optional[str] = None
    price: Optional[float] = Field(None, ge=0)
    category: Optional[str] = Field(None, max_length=100)
    sku: Optional[str] = Field(None, max_length=100)
    image_url: Optional[str] = None
    metadata: Optional[dict] = None
    is_active: Optional[bool] = None


class ProductResponse(ProductBase):
    """Schema for product response"""
    id: UUID
    organization_id: UUID
    is_active: bool
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class ProductFilters(BaseModel):
    """Filters for listing products"""
    category: Optional[str] = None
    is_active: Optional[bool] = True
    min_price: Optional[float] = None
    max_price: Optional[float] = None
    search: Optional[str] = None
    limit: int = Field(default=50, ge=1, le=100)
    offset: int = Field(default=0, ge=0)


class ProductSearchRequest(BaseModel):
    """Request schema for hybrid product search"""
    query: str = Field(..., min_length=1, max_length=500, description="Search query")
    search_type: str = Field(default="hybrid", description="literal, semantic, or hybrid")
    limit: int = Field(default=10, ge=1, le=50)


class ProductSearchResult(BaseModel):
    """Single product search result"""
    product: ProductResponse
    score: float
    match_type: str  # "literal" or "semantic"


class ProductSearchResponse(BaseModel):
    """Response schema for product search"""
    results: List[ProductSearchResult]
    total: int
    query: str