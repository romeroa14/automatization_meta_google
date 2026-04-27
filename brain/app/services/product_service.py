"""Product service - CRUD and search operations"""
from typing import List, Optional, Dict, Any
from uuid import UUID
from datetime import datetime
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import text, select
from sqlalchemy.dialects.postgresql import insert

from app.schemas.products import (
    ProductCreate,
    ProductUpdate,
    ProductResponse,
    ProductFilters,
    ProductSearchResult,
)
from app.core.db import async_session


class ProductService:
    """Service for product CRUD and search operations"""

    def __init__(self, organization_id: UUID):
        self.organization_id = organization_id

    async def create(self, data: ProductCreate) -> ProductResponse:
        """Create a new product"""
        async with async_session() as session:
            result = await session.execute(
                text("""
                    INSERT INTO products (
                        organization_id, name, description, price, category,
                        sku, image_url, metadata, is_active, created_at, updated_at
                    ) VALUES (
                        :organization_id, :name, :description, :price, :category,
                        :sku, :image_url, :metadata, TRUE, NOW(), NOW()
                    )
                    RETURNING id, organization_id, name, description, price,
                              category, sku, image_url, metadata, is_active,
                              created_at, updated_at
                """),
                {
                    "organization_id": self.organization_id,
                    "name": data.name,
                    "description": data.description,
                    "price": data.price,
                    "category": data.category,
                    "sku": data.sku,
                    "image_url": data.image_url,
                    "metadata": data.metadata,
                },
            )
            row = result.fetchone()
            await session.commit()
            return ProductResponse(
                id=row[0],
                organization_id=row[1],
                name=row[2],
                description=row[3],
                price=float(row[4]) if row[4] else None,
                category=row[5],
                sku=row[6],
                image_url=row[7],
                metadata=row[8],
                is_active=row[9],
                created_at=row[10],
                updated_at=row[11],
            )

    async def get(self, product_id: UUID) -> Optional[ProductResponse]:
        """Get a product by ID"""
        async with async_session() as session:
            result = await session.execute(
                text("""
                    SELECT id, organization_id, name, description, price,
                           category, sku, image_url, metadata, is_active,
                           created_at, updated_at
                    FROM products
                    WHERE id = :product_id AND organization_id = :organization_id
                """),
                {"product_id": product_id, "organization_id": self.organization_id},
            )
            row = result.fetchone()
            if not row:
                return None
            return ProductResponse(
                id=row[0],
                organization_id=row[1],
                name=row[2],
                description=row[3],
                price=float(row[4]) if row[4] else None,
                category=row[5],
                sku=row[6],
                image_url=row[7],
                metadata=row[8],
                is_active=row[9],
                created_at=row[10],
                updated_at=row[11],
            )

    async def list(self, filters: ProductFilters) -> List[ProductResponse]:
        """List products with filters"""
        async with async_session() as session:
            query = text("""
                SELECT id, organization_id, name, description, price,
                       category, sku, image_url, metadata, is_active,
                       created_at, updated_at
                FROM products
                WHERE organization_id = :organization_id
            """)
            params = {"organization_id": self.organization_id}

            if filters.category:
                query += " AND category = :category"
                params["category"] = filters.category

            if filters.is_active is not None:
                query += " AND is_active = :is_active"
                params["is_active"] = filters.is_active

            if filters.min_price is not None:
                query += " AND price >= :min_price"
                params["min_price"] = filters.min_price

            if filters.max_price is not None:
                query += " AND price <= :max_price"
                params["max_price"] = filters.max_price

            if filters.search:
                query += " AND (name ILIKE :search OR description ILIKE :search)"
                params["search"] = f"%{filters.search}%"

            query += " ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
            params["limit"] = filters.limit
            params["offset"] = filters.offset

            result = await session.execute(query, params)
            rows = result.fetchall()
            return [
                ProductResponse(
                    id=row[0],
                    organization_id=row[1],
                    name=row[2],
                    description=row[3],
                    price=float(row[4]) if row[4] else None,
                    category=row[5],
                    sku=row[6],
                    image_url=row[7],
                    metadata=row[8],
                    is_active=row[9],
                    created_at=row[10],
                    updated_at=row[11],
                )
                for row in rows
            ]

    async def update(
        self, product_id: UUID, data: ProductUpdate
    ) -> Optional[ProductResponse]:
        """Update a product"""
        async with async_session() as session:
            # Build dynamic update query
            updates = []
            params = {"product_id": product_id, "organization_id": self.organization_id}

            if data.name is not None:
                updates.append("name = :name")
                params["name"] = data.name
            if data.description is not None:
                updates.append("description = :description")
                params["description"] = data.description
            if data.price is not None:
                updates.append("price = :price")
                params["price"] = data.price
            if data.category is not None:
                updates.append("category = :category")
                params["category"] = data.category
            if data.sku is not None:
                updates.append("sku = :sku")
                params["sku"] = data.sku
            if data.image_url is not None:
                updates.append("image_url = :image_url")
                params["image_url"] = data.image_url
            if data.metadata is not None:
                updates.append("metadata = :metadata")
                params["metadata"] = data.metadata
            if data.is_active is not None:
                updates.append("is_active = :is_active")
                params["is_active"] = data.is_active

            if not updates:
                return await self.get(product_id)

            updates.append("updated_at = NOW()")

            query = text(f"""
                UPDATE products
                SET {', '.join(updates)}
                WHERE id = :product_id AND organization_id = :organization_id
                RETURNING id, organization_id, name, description, price,
                          category, sku, image_url, metadata, is_active,
                          created_at, updated_at
            """)

            result = await session.execute(query, params)
            row = result.fetchone()
            await session.commit()

            if not row:
                return None

            return ProductResponse(
                id=row[0],
                organization_id=row[1],
                name=row[2],
                description=row[3],
                price=float(row[4]) if row[4] else None,
                category=row[5],
                sku=row[6],
                image_url=row[7],
                metadata=row[8],
                is_active=row[9],
                created_at=row[10],
                updated_at=row[11],
            )

    async def delete(self, product_id: UUID) -> bool:
        """Delete a product"""
        async with async_session() as session:
            result = await session.execute(
                text("""
                    DELETE FROM products
                    WHERE id = :product_id AND organization_id = :organization_id
                """),
                {"product_id": product_id, "organization_id": self.organization_id},
            )
            await session.commit()
            return result.rowcount > 0

    async def search_literal(self, query: str, limit: int = 10) -> List[ProductResponse]:
        """Exact match search on products table"""
        async with async_session() as session:
            result = await session.execute(
                text("""
                    SELECT id, organization_id, name, description, price,
                           category, sku, image_url, metadata, is_active,
                           created_at, updated_at
                    FROM products
                    WHERE organization_id = :organization_id
                      AND is_active = TRUE
                      AND (
                          name ILIKE :query
                          OR sku ILIKE :query
                          OR category ILIKE :query
                      )
                    ORDER BY created_at DESC
                    LIMIT :limit
                """),
                {
                    "organization_id": self.organization_id,
                    "query": f"%{query}%",
                    "limit": limit,
                },
            )
            rows = result.fetchall()
            return [
                ProductResponse(
                    id=row[0],
                    organization_id=row[1],
                    name=row[2],
                    description=row[3],
                    price=float(row[4]) if row[4] else None,
                    category=row[5],
                    sku=row[6],
                    image_url=row[7],
                    metadata=row[8],
                    is_active=row[9],
                    created_at=row[10],
                    updated_at=row[11],
                )
                for row in rows
            ]

    async def search_semantic(
        self, query: str, limit: int = 10
    ) -> List[ProductSearchResult]:
        """Vector similarity search on product embeddings"""
        from app.core.config import settings
        from langchain_openai import OpenAIEmbeddings

        # Get embedding for query
        embeddings = OpenAIEmbeddings(api_key=settings.openai_api_key)
        query_embedding = embeddings.embed_query(query)

        async with async_session() as session:
            result = await session.execute(
                text("""
                    SELECT pe.product_id, p.id, p.organization_id, p.name,
                           p.description, p.price, p.category, p.sku,
                           p.image_url, p.metadata, p.is_active,
                           p.created_at, p.updated_at,
                           1 - (pe.embedding <=> :embedding::vector) as cosine_sim
                    FROM product_embeddings pe
                    JOIN products p ON pe.product_id = p.id
                    WHERE pe.organization_id = :organization_id
                      AND p.is_active = TRUE
                    ORDER BY pe.embedding <=> :embedding::vector
                    LIMIT :limit
                """),
                {
                    "organization_id": self.organization_id,
                    "embedding": str(query_embedding),
                    "limit": limit,
                },
            )
            rows = result.fetchall()
            return [
                ProductSearchResult(
                    product=ProductResponse(
                        id=row[1],
                        organization_id=row[2],
                        name=row[3],
                        description=row[4],
                        price=float(row[5]) if row[5] else None,
                        category=row[6],
                        sku=row[7],
                        image_url=row[8],
                        metadata=row[9],
                        is_active=row[10],
                        created_at=row[11],
                        updated_at=row[12],
                    ),
                    score=float(row[13]) if row[13] else 0.0,
                    match_type="semantic",
                )
                for row in rows
            ]

    async def search_hybrid(
        self, query: str, limit: int = 10
    ) -> List[ProductSearchResult]:
        """Combined literal + semantic search with reranking"""
        # Get literal results
        literal_results = await self.search_literal(query, limit=limit)

        # Get semantic results
        semantic_results = await self.search_semantic(query, limit=limit)

        # Combine and deduplicate
        seen_ids = set()
        combined = []

        for r in literal_results:
            if r.id not in seen_ids:
                seen_ids.add(r.id)
                combined.append(
                    ProductSearchResult(
                        product=r,
                        score=1.0,  # Exact match gets highest score
                        match_type="literal",
                    )
                )

        for r in semantic_results:
            if r.product.id not in seen_ids:
                seen_ids.add(r.product.id)
                combined.append(r)

        # Sort by score descending
        combined.sort(key=lambda x: x.score, reverse=True)

        return combined[:limit]