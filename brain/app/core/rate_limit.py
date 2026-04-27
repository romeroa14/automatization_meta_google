"""Rate limiting utilities per organization"""
from typing import Optional, Dict
from datetime import datetime, timedelta
from uuid import UUID
from app.core.config import settings


class RateLimiter:
    """Simple in-memory rate limiter per organization"""

    def __init__(self):
        self._requests: Dict[str, list] = {}

    def _get_key(self, org_id: str) -> str:
        """Get rate limit key for org + minute"""
        now = datetime.now()
        minute_key = now.strftime("%Y-%m-%d %H:%M")
        return f"{org_id}:{minute_key}"

    def check_rate_limit(
        self, org_id: str, limit: Optional[int] = None
    ) -> bool:
        """Check if org is within rate limit. Returns True if allowed."""
        if limit is None:
            limit = settings.rate_limit_per_minute

        key = self._get_key(org_id)
        now = datetime.now()

        # Clean old entries
        if key not in self._requests:
            self._requests = {
                k: v for k, v in self._requests.items()
                if v and (now - v[0]).total_seconds() < 120
            }
            self._requests[key] = []

        # Check current minute
        requests = self._requests.get(key, [])
        requests = [r for r in requests if (now - r).total_seconds() < 60]
        
        if len(requests) >= limit:
            return False

        requests.append(now)
        self._requests[key] = requests
        return True

    def get_remaining(self, org_id: str) -> int:
        """Get remaining requests for this minute"""
        limit = settings.rate_limit_per_minute
        key = self._get_key(org_id)
        requests = self._requests.get(key, [])
        return max(0, limit - len(requests))


# Singleton instance
rate_limiter = RateLimiter()