"""Organization configuration manager for per-org settings"""
from typing import Optional, Dict, Any
from uuid import UUID
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import text
from app.core.db import async_session


class OrgConfigManager:
    """Manages organization-specific configuration"""

    def __init__(self):
        pass

    async def get_org_settings(
        self, org_id: UUID, session: Optional[AsyncSession] = None
    ) -> Dict[str, Any]:
        """Get settings for a specific organization"""
        close_session = False
        if session is None:
            session = async_session()
            close_session = True

        try:
            result = await session.execute(
                text("""
                    SELECT openai_api_key_encrypted, custom_instructions,
                           rate_limit_override, features
                    FROM organization_settings
                    WHERE organization_id = :org_id
                """),
                {"org_id": org_id},
            )
            row = result.fetchone()
            if row:
                return {
                    "openai_api_key_encrypted": row[0],
                    "custom_instructions": row[1],
                    "rate_limit_override": row[2],
                    "features": row[3] or {},
                }
            return {}
        finally:
            if close_session:
                await session.close()

    async def get_openai_key(self, org_id: UUID) -> Optional[str]:
        """Get decrypted OpenAI API key for organization"""
        from app.core.encryption import decrypt

        settings = await self.get_org_settings(org_id)
        encrypted_key = settings.get("openai_api_key_encrypted")
        if encrypted_key:
            return decrypt(encrypted_key)
        return None

    async def get_rate_limit(self, org_id: UUID, default: int = 100) -> int:
        """Get rate limit for organization (or default)"""
        settings = await self.get_org_settings(org_id)
        return settings.get("rate_limit_override", default)

    async def get_custom_instructions(self, org_id: UUID) -> Optional[str]:
        """Get custom system instructions for organization"""
        settings = await self.get_org_settings(org_id)
        return settings.get("custom_instructions")


# Singleton instance
org_config_manager = OrgConfigManager()