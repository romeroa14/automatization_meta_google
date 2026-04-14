from pydantic import BaseModel, Field

class ChatRequest(BaseModel):
    organization_id: str = Field(..., description="The ID of the organization")
    whatsapp_phone_number_id: str = Field(..., description="The ID of the WhatsApp phone number")
    customer_id: str = Field(..., description="The ID of the customer")
    platform: str = Field(..., description="The platform the message is coming from (e.g., whatsapp)")
    message: str = Field(..., description="The actual message content")
