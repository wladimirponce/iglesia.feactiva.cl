from typing import Any
import httpx

from .config import Settings


class PhpAgentClient:
    def __init__(self, settings: Settings) -> None:
        self.settings = settings

    def _headers(self) -> dict[str, str]:
        return {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-Integration-Key": self.settings.php_integration_key,
        }

    async def resolve_ontology(self, tenant_id: int, user_id: int, message_text: str) -> dict[str, Any]:
        payload = {
            "tenant_id": tenant_id,
            "user_id": user_id,
            "message_text": message_text,
        }
        return await self._post(self.settings.php_ontology_url, payload)

    async def execute_agent(
        self,
        tenant_id: int,
        user_id: int,
        conversation_id: int | None,
        message_text: str,
    ) -> dict[str, Any]:
        payload = {
            "tenant_id": tenant_id,
            "user_id": user_id,
            "conversation_id": conversation_id,
            "message_text": message_text,
        }
        return await self._post(self.settings.php_agent_execute_url, payload)

    async def _post(self, url: str, payload: dict[str, Any]) -> dict[str, Any]:
        if not self.settings.php_integration_key:
            raise RuntimeError("PHP_INTEGRATION_KEY is required")

        async with httpx.AsyncClient(timeout=self.settings.php_timeout_seconds) as client:
            response = await client.post(url, json=payload, headers=self._headers())

        try:
            decoded = response.json()
        except ValueError as exc:
            raise RuntimeError("PHP returned non-JSON response") from exc

        if response.status_code < 200 or response.status_code >= 300 or decoded.get("success") is not True:
            raise RuntimeError(str(decoded.get("error", {}).get("code", "PHP_AGENT_ERROR")))

        data = decoded.get("data")
        return data if isinstance(data, dict) else {}
