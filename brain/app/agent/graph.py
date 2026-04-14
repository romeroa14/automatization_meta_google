from langgraph.graph import StateGraph, END
from langgraph.checkpoint.postgres.aio import AsyncPostgresSaver
from contextlib import asynccontextmanager
from app.agent.state import AgentState
from app.agent.nodes.ingest import ingest_node
from app.agent.nodes.retrieve import retrieve_node
from app.agent.nodes.classify import classify_node
from app.agent.nodes.respond import respond_node
import os

DATABASE_URL = os.getenv("DATABASE_URL", "postgresql+asyncpg://postgres:postgres@localhost:5432/postgres")

def build_graph():
    workflow = StateGraph(AgentState)

    workflow.add_node("ingest", ingest_node)
    workflow.add_node("classify", classify_node)
    workflow.add_node("retrieve", retrieve_node)
    workflow.add_node("respond", respond_node)

    workflow.set_entry_point("classify")
    workflow.add_edge("classify", "retrieve")
    workflow.add_edge("retrieve", "respond")
    workflow.add_edge("respond", END)

    return workflow

@asynccontextmanager
async def get_compiled_graph():
    workflow = build_graph()
    
    async with AsyncPostgresSaver.from_conn_string(DATABASE_URL) as checkpointer:
        await checkpointer.setup()
        app = workflow.compile(checkpointer=checkpointer)
        yield app
