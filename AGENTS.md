# AGENTS.md

## Learned User Preferences

- When working on n8n workflows or HowTo-Genie automation, use Context7, n8n-mcp, awesome-n8n-templates, and n8n-workflows for documentation and pattern lookup.
- In GSD discuss-phase, user often delegates implementation choices to the planner (e.g. "you decide").

## Learned Workspace Facts

- Ollama LLM calls are centralized in a single sub-workflow (Ollama Agent Central); all 8 agents in the main pipeline use this sub-workflow rather than direct HTTP nodes.
- More affiliate platforms (Digistore24, Commission Junction, Warrior Plus, etc.) are planned for a future phase; Phase 6 / current scope uses Muncheye and CBEngine only.
- Islamic content was removed from v2.0 milestone; user keeps halal filtering for income but does not want Islamic-calendar-themed content for tier-1 affiliate focus.
