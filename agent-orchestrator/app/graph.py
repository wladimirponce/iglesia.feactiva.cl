from typing import Literal

from langgraph.graph import END, StateGraph

from .memory import MemoryStore
from .models import AgentState
from .php_client import PhpAgentClient


SUGGESTIONS = "Puedo ayudarte con: personas, familias, finanzas, discipulado o pastoral."
UNAVAILABLE = "Esa funcion aun no esta disponible."


class AgentOrchestratorGraph:
    def __init__(self, php_client: PhpAgentClient, memory_store: MemoryStore) -> None:
        self.php_client = php_client
        self.memory_store = memory_store
        self.graph = self._build_graph()

    def _build_graph(self):
        graph = StateGraph(AgentState)
        graph.add_node("receive_message", self.receive_message)
        graph.add_node("call_php_ontology", self.call_php_ontology)
        graph.add_node("detect_missing_fields", self.detect_missing_fields)
        graph.add_node("ask_for_missing_fields", self.ask_for_missing_fields)
        graph.add_node("check_requires_confirmation", self.check_requires_confirmation)
        graph.add_node("execute_tool_via_php_agent", self.execute_tool_via_php_agent)
        graph.add_node("compose_response", self.compose_response)

        graph.set_entry_point("receive_message")
        graph.add_edge("receive_message", "call_php_ontology")
        graph.add_edge("call_php_ontology", "detect_missing_fields")
        graph.add_conditional_edges(
            "detect_missing_fields",
            self.route_after_missing_detection,
            {
                "ask_for_missing_fields": "ask_for_missing_fields",
                "check_requires_confirmation": "check_requires_confirmation",
                "compose_response": "compose_response",
            },
        )
        graph.add_edge("ask_for_missing_fields", "compose_response")
        graph.add_conditional_edges(
            "check_requires_confirmation",
            self.route_after_confirmation_check,
            {
                "execute_tool_via_php_agent": "execute_tool_via_php_agent",
                "compose_response": "compose_response",
            },
        )
        graph.add_edge("execute_tool_via_php_agent", "compose_response")
        graph.add_edge("compose_response", END)
        return graph.compile()

    async def invoke(self, state: AgentState) -> AgentState:
        return await self.graph.ainvoke(state)

    async def receive_message(self, state: AgentState) -> AgentState:
        memory = self.memory_store.get(state["tenant_id"], state["user_id"], state.get("conversation_id"))
        state["effective_message_text"] = state["message_text"].strip()

        if memory.pending_message_text:
            state["effective_message_text"] = f"{memory.pending_message_text} {state['effective_message_text']}".strip()

        self.memory_store.remember_user_message(state["tenant_id"], state["user_id"], state.get("conversation_id"), state["message_text"])
        return state

    async def call_php_ontology(self, state: AgentState) -> AgentState:
        state["ontology"] = await self.php_client.resolve_ontology(
            state["tenant_id"],
            state["user_id"],
            state["effective_message_text"],
        )
        return state

    async def detect_missing_fields(self, state: AgentState) -> AgentState:
        ontology = state.get("ontology") or {}

        if ontology.get("resolved") is not True:
            state["status"] = "completed"
            state["response_text"] = SUGGESTIONS
            state["route"] = "compose_response"
            self.memory_store.clear_pending(state["tenant_id"], state["user_id"], state.get("conversation_id"))
            return state

        if not ontology.get("tool_name"):
            state["status"] = "completed"
            state["response_text"] = UNAVAILABLE
            state["route"] = "compose_response"
            self.memory_store.clear_pending(state["tenant_id"], state["user_id"], state.get("conversation_id"))
            return state

        missing = ontology.get("missing_fields")
        state["missing_fields"] = missing if isinstance(missing, list) else []
        if state["missing_fields"]:
            state["status"] = "waiting_for_input"
            state["route"] = "ask_for_missing_fields"
            memory = self.memory_store.get(state["tenant_id"], state["user_id"], state.get("conversation_id"))
            memory.pending_message_text = state["effective_message_text"]
            memory.pending_missing_fields = state["missing_fields"]
            return state

        state["route"] = "check_requires_confirmation"
        return state

    async def ask_for_missing_fields(self, state: AgentState) -> AgentState:
        missing_fields = state.get("missing_fields") or []
        fields = ", ".join(missing_fields) if missing_fields else "datos obligatorios"
        state["response_text"] = f"Me faltan datos para ejecutar esa accion. Indica: {fields}."
        return state

    async def check_requires_confirmation(self, state: AgentState) -> AgentState:
        ontology = state.get("ontology") or {}
        if ontology.get("requires_confirmation") is True:
            state["status"] = "requires_confirmation"
            state["proposed_action"] = {
                "action": ontology.get("action"),
                "tool_name": ontology.get("tool_name"),
                "object_type": ontology.get("object_type"),
                "extracted_fields": ontology.get("extracted_fields") if isinstance(ontology.get("extracted_fields"), dict) else {},
                "sensitive_level": ontology.get("sensitive_level"),
            }
            state["response_text"] = "Esta accion requiere confirmacion antes de ejecutarse."
            memory = self.memory_store.get(state["tenant_id"], state["user_id"], state.get("conversation_id"))
            memory.proposed_action = state["proposed_action"]
            return state

        state["route"] = "execute_tool_via_php_agent"
        return state

    async def execute_tool_via_php_agent(self, state: AgentState) -> AgentState:
        state["php_result"] = await self.php_client.execute_agent(
            state["tenant_id"],
            state["user_id"],
            state.get("conversation_id"),
            state["effective_message_text"],
        )
        state["status"] = "completed"
        state["response_text"] = str((state.get("php_result") or {}).get("response_text") or "")
        self.memory_store.clear_pending(state["tenant_id"], state["user_id"], state.get("conversation_id"))
        return state

    async def compose_response(self, state: AgentState) -> AgentState:
        if not state.get("response_text"):
            state["status"] = "failed"
            state["response_text"] = "No fue posible procesar la solicitud."
        return state

    def route_after_missing_detection(
        self,
        state: AgentState,
    ) -> Literal["ask_for_missing_fields", "check_requires_confirmation", "compose_response"]:
        if state.get("route") == "ask_for_missing_fields":
            return "ask_for_missing_fields"
        if state.get("route") == "check_requires_confirmation":
            return "check_requires_confirmation"
        return "compose_response"

    def route_after_confirmation_check(
        self,
        state: AgentState,
    ) -> Literal["execute_tool_via_php_agent", "compose_response"]:
        if state.get("route") == "execute_tool_via_php_agent":
            return "execute_tool_via_php_agent"
        return "compose_response"
