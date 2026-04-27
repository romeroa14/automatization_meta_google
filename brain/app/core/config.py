from pydantic import Field
from pydantic_settings import BaseSettings
from functools import lru_cache
import os


class Settings(BaseSettings):
    """Global application settings"""
    database_url: str = Field(
        default=os.getenv("DATABASE_URL", "postgresql+asyncpg://postgres:postgres@localhost:5432/postgres")
    )
    vector_database_url: str = Field(
        default=os.getenv("VECTOR_DATABASE_URL", "postgresql+asyncpg://postgres:postgres@localhost:5432/postgres")
    )
    openai_api_key: str = Field(default=os.getenv("OPENAI_API_KEY", ""))
    deepseek_api_key: str = Field(default=os.getenv("DEEPSEEK_API_KEY", ""))
    deepseek_base_url: str = Field(default=os.getenv("DEEPSEEK_BASE_URL", "https://api.deepseek.com"))
    deepseek_model: str = Field(default=os.getenv("DEEPSEEK_MODEL", "deepseek-chat"))
    storage_path: str = Field(
        default=os.getenv("STORAGE_PATH", "/var/www/html/automatization_fb_google/storage")
    )
    rate_limit_per_minute: int = Field(default=100)
    laravel_api_url: str = Field(
        default=os.getenv("LARAVEL_API_URL", "http://localhost:8000/api")
    )
    laravel_api_key: str = Field(default=os.getenv("LARAVEL_API_KEY", ""))
    encryption_key: str = Field(default=os.getenv("ENCRYPTION_KEY", ""))

    class Config:
        env_file = ".env"
        env_file_encoding = "utf-8"
        extra = "ignore"


@lru_cache
def get_settings() -> Settings:
    """Get cached settings instance"""
    return Settings()


# Singleton instance for convenience
settings = get_settings()