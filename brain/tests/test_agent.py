import pytest
from app.agent.state import AgentState

def test_agent_state_initialization():
    state = AgentState(
        messages=[],
        organization_id="org_123",
        whatsapp_phone_number_id="phone_123",
        customer_id="cust_123",
        platform="whatsapp",
        intent="unknown"
    )
    assert state["organization_id"] == "org_123"
    assert state["platform"] == "whatsapp"
    assert "messages" in state
