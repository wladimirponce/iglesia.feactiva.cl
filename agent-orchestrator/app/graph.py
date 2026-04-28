import re
import unicodedata
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
            if self.is_resolvable_by_php_agent(state["missing_fields"], state["effective_message_text"]):
                # Ontology can detect gaps, but PHP AgentService is the final authority
                # for aliases/defaults such as "caja principal" or default finance accounts.
                state["route"] = "check_requires_confirmation"
                return state

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
        php_result = state.get("php_result") or {}
        tool_result = php_result.get("tool") if isinstance(php_result.get("tool"), dict) else {}
        output = tool_result.get("output") if isinstance(tool_result.get("output"), dict) else {}
        php_missing_fields = output.get("missing_fields") if isinstance(output.get("missing_fields"), list) else []

        state["response_text"] = str(php_result.get("response_text") or "")

        if tool_result.get("status") == "failed" and php_missing_fields:
            state["status"] = "waiting_for_input"
            state["missing_fields"] = [str(field) for field in php_missing_fields]
            memory = self.memory_store.get(state["tenant_id"], state["user_id"], state.get("conversation_id"))
            memory.pending_message_text = state["effective_message_text"]
            memory.pending_missing_fields = state["missing_fields"]
            return state

        state["status"] = "completed"
        state["missing_fields"] = []
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

    def is_resolvable_by_php_agent(self, missing_fields: list[str], input_text: str) -> bool:
        text = self._normalize_text(input_text)

        for field in missing_fields:
            normalized_field = self._normalize_text(str(field))

            if normalized_field in {"monto", "amount"} and not self._has_amount(text):
                return False

            if normalized_field in {"cuenta_id", "cuenta", "account_id"} and not self._has_account_reference(text):
                return False

            if normalized_field in {"persona_id", "persona", "person_id"} and not self._has_person_reference(text):
                return False

            if normalized_field in {"fecha", "fecha_inicio", "fecha_fin", "fecha_hora", "date", "datetime"} and not self._has_time_reference(text):
                return False

            known_resolvable = {
                "categoria_id",
                "subtipo",
                "tipo",
                "medio_pago",
                "titulo",
                "detalle",
                "descripcion",
            }
            if normalized_field not in known_resolvable and normalized_field not in {
                "monto",
                "amount",
                "cuenta_id",
                "cuenta",
                "account_id",
                "persona_id",
                "persona",
                "person_id",
                "fecha",
                "fecha_inicio",
                "fecha_fin",
                "fecha_hora",
                "date",
                "datetime",
            }:
                return False

        return True

    def _normalize_text(self, value: str) -> str:
        normalized = unicodedata.normalize("NFD", value.lower())
        without_accents = "".join(ch for ch in normalized if unicodedata.category(ch) != "Mn")
        return re.sub(r"\s+", " ", without_accents).strip()

    def _has_amount(self, text: str) -> bool:
        return re.search(r"\b\d+(?:[.,]\d+)?\b", text) is not None

    def _has_account_reference(self, text: str) -> bool:
        account_words = {
            "caja",
            "caja principal",
            "banco",
            "cuenta",
            "cuenta corriente",
            "corriente",
            "efectivo",
            "transferencia",
            "principal",
        }
        return any(word in text for word in account_words)

    def _has_person_reference(self, text: str) -> bool:
        if re.search(r"\bpersona\s+\d+\b", text):
            return True

        if re.search(r"\bbuscar\s+persona\s+[a-z][a-z\s]{1,50}\b", text):
            return True

        stop_words = {
            "ofrenda",
            "diezmo",
            "donacion",
            "ingreso",
            "egreso",
            "gasto",
            "caja",
            "caja principal",
            "banco",
            "abril",
            "hoy",
            "manana",
            "las",
            "llamar",
            "recordar",
            "recuerdame",
        }
        for match in re.finditer(r"\b(?:para|de|a)\s+([a-z]+(?:\s+[a-z]+){0,3})\b", text):
            words = [word for word in match.group(1).split() if word not in stop_words]
            if words:
                return True

        return False

    def _has_time_reference(self, text: str) -> bool:
        month_names = (
            "enero|febrero|marzo|abril|mayo|junio|julio|agosto|"
            "septiembre|setiembre|octubre|noviembre|diciembre"
        )
        temporal_patterns = [
            r"\b(hoy|manana|ayer|pasado manana|esta semana|proxima semana|este mes|proximo mes)\b",
            rf"\b({month_names})\b",
            r"\b\d{4}-\d{2}-\d{2}\b",
            r"\b\d{1,2}/\d{1,2}(?:/\d{2,4})?\b",
            r"\b\d{1,2}:\d{2}\b",
            r"\ba las \d{1,2}\b",
        ]
        return any(re.search(pattern, text) for pattern in temporal_patterns)
