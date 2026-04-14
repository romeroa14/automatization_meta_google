import pytest
from unittest.mock import patch, MagicMock
from app.services.vector_store import get_vector_store

@pytest.mark.asyncio
async def test_vector_store_requires_organization_id():
    with pytest.raises(ValueError, match="organization_id is required"):
        await get_vector_store(organization_id=None)

@pytest.mark.asyncio
@patch('app.services.vector_store.PGVector')
async def test_vector_store_initialization(mock_pgvector):
    mock_instance = MagicMock()
    mock_pgvector.return_value = mock_instance
    
    store = await get_vector_store(organization_id="org_123")
    assert store is not None
    assert store.filter == {"organization_id": "org_123"}
