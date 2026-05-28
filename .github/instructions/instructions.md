
# Assistant Instructions for this Repository — FINAL (Enforced)

Purpose
- Provide a concise, actionable, and strictly enforced set of rules the AI assistant must follow when working in this repository.

Scope
- Applies to all files, scripts, tests, docs, CI, build artifacts, and tasks in this repository. These rules are mandatory and must be followed on every edit or guidance the assistant produces.

Mandates (Strict Requirements)
- Use the `manage_todo_list` tool to create and maintain a short plan for every multi-step task. Always update the plan as work progresses.
- Preface any set of file changes or multi-action tool calls with a 1–2 sentence preamble summarizing what you'll do and why.
- Use `apply_patch` for all repository file edits. Do not use alternative patching approaches.
- When naming or editing files, follow the project's Separation of Concerns (SoC): place code in the correct layers (routes, controllers, services, repositories, jobs, models, views, tests) and avoid cross-layer leakage.
- Follow the project's built artifacts and conventions: generated code, migration patterns, naming, and folder structure must match existing patterns.
- Always run the project's formatter on modified files: for PHP files use `vendor/bin/pint --dirty --format agent`.
- When changing behavior, add or update Pest tests and run the affected tests locally. Do not finalize changes that decrease test coverage without explicit approval.
- Do not volunteer the model name unless explicitly asked.

Coding & Review Practices
- Make minimal, focused, and reversible changes that honor existing style and architecture. Solve root causes, not symptoms.
- Add or update tests for any behavior change. Prefer unit and feature Pest tests located under `tests/`.
- Only create new top-level folders with explicit approval.
- Use named routes, resources, and Eloquent API Resources where appropriate.

Tooling & CI
- Before marking work done, ensure formatting and tests pass locally: run the formatter and the test suite (or the subset relevant to your changes).
- If CI or local environment prevents you from running tests, clearly state why and what would be required (e.g., environment variables, external services).

Ambiguities / No Exceptions
- These rules are strict and apply everywhere in the repository. If a particular action cannot follow a rule, document the blocker and request explicit approval.

Examples (How the assistant should behave)
- "Implement feature X that requires a migration, model and API endpoint." — Assistant must: create a `manage_todo_list` plan, preface the patch with a 1–2 sentence preamble, apply changes with `apply_patch`, add/update tests, run `vendor/bin/pint` and the affected tests.
- "Patch `app/Http/Requests/UpdateProfileRequest.php`" — Assistant must: preface, patch via `apply_patch`, run `vendor/bin/pint`, add/update tests in `tests/Feature` or `tests/Unit`, and run tests.

Next Steps
- This file is finalized per request: rules apply everywhere and are strict. If you want, I can now run formatting and the test subset for any upcoming change, or commit and open a PR for this file.

---
Finalized by the assistant based on user confirmation.

