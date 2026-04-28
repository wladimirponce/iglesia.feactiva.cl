from collections import defaultdict, deque
from dataclasses import dataclass, field


@dataclass
class ConversationMemory:
    messages: deque[str] = field(default_factory=lambda: deque(maxlen=6))
    pending_message_text: str | None = None
    pending_missing_fields: list[str] = field(default_factory=list)
    proposed_action: dict | None = None


class MemoryStore:
    def __init__(self, max_turns: int = 6) -> None:
        self.max_turns = max_turns
        self._items: dict[str, ConversationMemory] = defaultdict(self._new_memory)

    def _new_memory(self) -> ConversationMemory:
        return ConversationMemory(messages=deque(maxlen=self.max_turns))

    def key(self, tenant_id: int, user_id: int, conversation_id: int | None) -> str:
        return f"{tenant_id}:{user_id}:{conversation_id or 0}"

    def get(self, tenant_id: int, user_id: int, conversation_id: int | None) -> ConversationMemory:
        return self._items[self.key(tenant_id, user_id, conversation_id)]

    def remember_user_message(self, tenant_id: int, user_id: int, conversation_id: int | None, message: str) -> None:
        self.get(tenant_id, user_id, conversation_id).messages.append(message)

    def clear_pending(self, tenant_id: int, user_id: int, conversation_id: int | None) -> None:
        memory = self.get(tenant_id, user_id, conversation_id)
        memory.pending_message_text = None
        memory.pending_missing_fields = []
        memory.proposed_action = None
