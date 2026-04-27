from app.agent.state import AgentState
from app.agent.tools.crm import search_products


async def retrieve_node(state: AgentState) -> AgentState:
    """
    Retrieve node for product search during agent execution.
    
    Extracts the user's query from messages and searches products
    using the RAG pipeline, ثم يخزن النتائج لاستخدامها في توليد الرد.
    """
    # Extract user query from messages
    messages = state.get("messages", [])
    user_query = None
    
    if messages:
        # Get the last message (user's input)
        last_message = messages[-1]
        # Support both dict messages and objects with content attribute
        if isinstance(last_message, dict):
            user_query = last_message.get("content", "")
        elif hasattr(last_message, "content"):
            user_query = last_message.content
        
        # Also check for 'text' field in dict messages
        if not user_query and isinstance(last_message, dict):
            user_query = last_message.get("text", "")
    
    organization_id = state.get("organization_id")
    
    # إذا لم найдет query أو organization_id، نرجع الـ state كما هي
    if not user_query or not organization_id:
        return state
    
    try:
        # Call search_products tool with hybrid search
        results = await search_products(
            query=user_query,
            organization_id=organization_id,
            search_type="hybrid",
            limit=10
        )
        
        # Store retrieval results in state for downstream nodes
        state["retrieved_products"] = results
        
    except Exception as e:
        # On error, store empty results
        state["retrieved_products"] = {"error": str(e), "products": [], "count": 0}
    
    return state
