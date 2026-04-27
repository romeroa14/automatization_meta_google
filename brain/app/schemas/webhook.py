from pydantic import BaseModel, Field
from typing import Optional, List, Dict, Any


class WebhookChatRequest(BaseModel):
    """Webhook request from Laravel for chat"""
    organization_id: str = Field(..., description="Organization ID")
    platform: str = Field(..., description="Platform name", example="whatsapp")
    customer_id: str = Field(..., description="Customer ID")
    customer_phone: Optional[str] = Field(None, description="Customer phone number")
    message: str = Field(..., min_length=1, description="Message content")
    conversation_history: List[Dict[str, str]] = Field(
        default_factory=list,
        description="Previous conversation messages"
    )
    thread_id: Optional[str] = None


class Action(BaseModel):
    """Action to be taken after response"""
    tool: str = Field(..., description="Tool name to call")
    params: Dict[str, Any] = Field(default_factory=dict)


class WebhookChatResponse(BaseModel):
    """Webhook response to Laravel"""
    reply: str = Field(..., description="Assistant reply")
    actions: List[Action] = Field(default_factory=list)
    conversation_id: str


class WebhookEventRequest(BaseModel):
    """Base webhook event request"""
    organization_id: str
    lead_id: int
    lead_name: Optional[str] = None
    lead_phone: Optional[str] = None
    timestamp: Optional[str] = None


class WebhookLeadCreatedRequest(WebhookEventRequest):
    """Webhook request for lead created event"""
    pass


class WebhookLeadUpdatedRequest(WebhookEventRequest):
    """Webhook request for lead updated event"""
    old_status: Optional[str] = None
    new_status: Optional[str] = None


class WebhookEventResponse(BaseModel):
    """Response for webhook events"""
    success: bool
    message: str = "Event processed"
    actions: List[Action] = Field(default_factory=list)