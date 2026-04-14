# Skill Registry

**Delegator use only.** Any agent that launches sub-agents reads this registry to resolve compact rules, then injects them directly into sub-agent prompts. Sub-agents do NOT read this registry or individual SKILL.md files.

## User Skills

| Trigger | Skill | Path |
|---------|-------|------|
| When creating a pull request, opening a PR, or preparing changes for review | branch-pr | /home/alfredo/.config/opencode/skills/branch-pr/SKILL.md |
| When writing Go tests, using teatest, or adding test coverage | go-testing | /home/alfredo/.config/opencode/skills/go-testing/SKILL.md |
| When creating a GitHub issue, reporting a bug, or requesting a feature | issue-creation | /home/alfredo/.config/opencode/skills/issue-creation/SKILL.md |
| When user says "judgment day", "dual review", "juzgar" | judgment-day | /home/alfredo/.config/opencode/skills/judgment-day/SKILL.md |
| When user asks to create a new skill, add agent instructions | skill-creator | /home/alfredo/.config/opencode/skills/skill-creator/SKILL.md |

## Compact Rules

Pre-digested rules per skill. Delegators copy matching blocks into sub-agent prompts as `## Project Standards (auto-resolved)`.

### branch-pr
- Every PR MUST link an approved issue — no exceptions
- Every PR MUST have exactly one `type:*` label
- Automated checks must pass before merge is possible
- Blank PRs without issue linkage will be blocked by GitHub Actions
- Branch names MUST match: `^(feat|fix|chore|docs|style|refactor|perf|test|build|ci|revert)\/[a-z0-9._-]+$`
- Use conventional commits: `type(scope): description`

### issue-creation
- Blank issues are disabled — MUST use a template (bug report or feature request)
- Every issue gets `status:needs-review` automatically on creation
- A maintainer MUST add `status:approved` before any PR can be opened
- Questions go to Discussions, not issues

### judgment-day
- Launch two independent blind judge sub-agents simultaneously to review the same target
- Synthesize their findings, apply fixes, and re-judge until both pass
- Escalate after 2 iterations if consensus is not reached

## Project Conventions

| File | Path | Notes |
|------|------|-------|
| No convention files found | — | Project has no AGENTS.md, CLAUDE.md, or .cursorrules |

Read the convention files listed above for project-specific patterns and rules. All referenced paths have been extracted — no need to read index files to discover more.
