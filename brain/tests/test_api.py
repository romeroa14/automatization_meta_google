from fastapi.testclient import TestClient
from unittest.mock import patch, AsyncMock, MagicMock
from app.main import app

client = TestClient(app)

def test_chat_endpoint_validation():
    # Test missing required fields
    response = client.post("/api/v1/chat", json={})
    assert response.status_code == 422

@patch("app.main.get_compiled_graph")
def test_chat_endpoint_success(mock_get_compiled_graph):
    # Mock the graph invocation
    mock_app = AsyncMock()
    mock_app.ainvoke.return_value = {
        "messages": [("ai", "Hello, I am the AI response")]
    }
    
    # Mock the async context manager
    mock_context_manager = AsyncMock()
    mock_context_manager.__aenter__.return_value = mock_app
    mock_get_compiled_graph.return_value = mock_context_manager
    
    # Test valid request
    payload = {
        "organization_id": "org_123",
        "whatsapp_phone_number_id": "phone_123",
        "customer_id": "cust_123",
        "platform": "whatsapp",
        "message": "Hello world"
    }
    response = client.post("/api/v1/chat", json=payload)
    assert response.status_code == 200
    assert "response" in response.json()
    assert response.json()["response"] == "Hello, I am the AI response"
    
    # Verify the graph was called with correct thread_id
    mock_app.ainvoke.assert_called_once()
    args, kwargs = mock_app.ainvoke.call_args
    assert args[1]["configurable"]["thread_id"] == "org_123:whatsapp:cust_123"
