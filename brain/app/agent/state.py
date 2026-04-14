from typing import TypedDict, List, Any

class AgentState(TypedDict):
    messages: List[Any]
    organization_id: str
    whatsapp_phone_number_id: str
    customer_id: str
    platform: str
    intent: str
