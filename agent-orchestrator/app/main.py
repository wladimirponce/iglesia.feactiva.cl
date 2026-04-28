from fastapi import FastAPI

from .config import get_settings
from .graph import AgentOrchestratorGraph
from .memory import MemoryStore
from .models import InvokeRequest, InvokeResponse
from .php_client import PhpAgentClient


settings = get_settings()
memory_store = MemoryStore(max_turns=settings.short_memory_turns)
php_client = PhpAgentClient(settings)
orchestrator = AgentOrchestratorGraph(php_client, memory_store)

app = FastAPI(title="FeActiva Agent Orchestrator", version="0.1.0")


@app.get("/health")
async def health() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/invoke", response_model=InvokeResponse)
async def invoke(payload: InvokeRequest) -> InvokeResponse:
    state = {
        "tenant_id": payload.tenant_id,
        "user_id": payload.user_id,
        "conversation_id": payload.conversation_id,
        "message_text": payload.message_text,
        "effective_message_text": payload.message_text,
        "status": "completed",
        "missing_fields": [],
        "route": "execute",
    }
    result = await orchestrator.invoke(state)
    return InvokeResponse(
        response_text=str(result.get("response_text") or ""),
        status=result.get("status", "failed"),
        missing_fields=result.get("missing_fields") or [],
        proposed_action=result.get("proposed_action"),
    )
