from functools import lru_cache
from pydantic import Field
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8", extra="ignore")

    php_ontology_url: str = Field(default="http://127.0.0.1:8000/internal/agent/ontology.php", alias="PHP_ONTOLOGY_URL")
    php_agent_execute_url: str = Field(default="http://127.0.0.1:8000/internal/agent/execute.php", alias="PHP_AGENT_EXECUTE_URL")
    php_integration_key: str = Field(default="", alias="PHP_INTEGRATION_KEY")
    php_timeout_seconds: float = Field(default=8.0, alias="PHP_TIMEOUT_SECONDS")
    short_memory_turns: int = Field(default=6, alias="SHORT_MEMORY_TURNS")


@lru_cache
def get_settings() -> Settings:
    return Settings()
