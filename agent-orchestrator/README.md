# Agent Orchestrator

Orquestador externo inicial para FeActiva Iglesia SaaS.

Este servicio usa FastAPI + LangGraph para manejar conversaciones multi-step, memoria corta, datos faltantes y confirmaciones. No ejecuta SQL, no accede a MySQL y no inventa herramientas: solo llama endpoints internos del SaaS protegidos por `X-Integration-Key`.

Ontology/orchestrator puede detectar campos faltantes como senal temprana, pero PHP AgentService es la autoridad final para resolver aliases y defaults antes de decidir si debe pedir datos al usuario.

## Endpoints

```http
POST /invoke
```

Input:

```json
{
  "tenant_id": 1,
  "user_id": 1,
  "conversation_id": 10,
  "message_text": "registra una ofrenda"
}
```

Output:

```json
{
  "response_text": "...",
  "status": "completed",
  "missing_fields": [],
  "proposed_action": null
}
```

## Ejecutar local

```bash
cd agent-orchestrator
python -m venv .venv
.venv/Scripts/activate
pip install -r requirements.txt
copy .env.example .env
uvicorn app.main:app --host 127.0.0.1 --port 8100
```

## Reglas

- LangGraph no ejecuta SQL.
- LangGraph no accede directo a MySQL.
- LangGraph no inventa tools.
- Toda acción real pasa por PHP Agent Core.
- Toda auditoría real sigue en `agent_actions` y `agent_audit_logs`.
