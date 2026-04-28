from typing import Any, Literal
from typing_extensions import TypedDict
from pydantic import BaseModel, Field


AgentStatus = Literal["completed", "waiting_for_input", "requires_confirmation", "failed"]


class InvokeRequest(BaseModel):
    tenant_id: int = Field(gt=0)
    user_id: int = Field(gt=0)
    conversation_id: int | None = Field(default=None)
    message_text: str = Field(min_length=1, max_length=4000)


class InvokeResponse(BaseModel):
    response_text: str
    status: AgentStatus
    missing_fields: list[str] = Field(default_factory=list)
    proposed_action: dict[str, Any] | None = None


class AgentState(TypedDict, total=False):
    tenant_id: int
    user_id: int
    conversation_id: int | None
    message_text: str
    effective_message_text: str
    ontology: dict[str, Any] | None
    php_result: dict[str, Any] | None
    response_text: str
    status: AgentStatus
    missing_fields: list[str]
    proposed_action: dict[str, Any] | None
    route: str
