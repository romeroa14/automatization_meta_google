from typing import TypedDict, List, Any, Optional, Dict


class AgentState(TypedDict):
    messages: List[Any]
    organization_id: str
    whatsapp_phone_number_id: str
    customer_id: str
    platform: str
    intent: str
    retrieved_products: Optional[Dict[str, Any]]
