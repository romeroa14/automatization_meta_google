from pydantic import BaseModel, Field
from typing import Optional

# Existing ChatRequest - keep for backwards compatibility
class ChatRequest(BaseModel):
    organization_id: str = Field(...)
    whatsapp_phone_number_id: str = Field(...)
    customer_id: str = Field(...)
    platform: str = Field(...)
    message: str = Field(...)