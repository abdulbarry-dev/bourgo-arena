---
trigger: always_on
---

# Antigravity Agent Access Control - Laravel API Focus

version: 1.0

scope:
  include:
    - "app/**"           # Core logic (Models, Controllers, Providers)
    - "routes/**"        # API and Web route definitions
    - "database/**"      # Migrations, Seeders, and Factories
    - "config/**"        # Application configuration files
    - "resources/views/**" # (Optional) Include if using Blade for emails/docs
    - "composer.json"    # Project dependencies
    - ".env.example"     # Structure of environment variables

  exclude:
    # High-Volume / Credit Draining Directories
    - "**/vendor/**"     # Laravel dependencies (CRITICAL EXCLUSION)
    - "**/node_modules/**"
    - "storage/**"       # Logs, cached views, and uploaded files
    - "bootstrap/cache/**"
    - "public/build/**"
    - "public/storage/**"

    # Testing & Tooling (Exclude unless debugging tests)
    - "tests/**"         
    - ".phpunit.cache/**"

    # IDE & System Files
    - ".idea/**"
    - ".vscode/**"
    - ".git/**"
    - ".env"             # Security: Never let the agent read your live secrets

constraints:

- "The agent must treat 'app/Http/Controllers' and 'app/Models' as primary context."
- "Ignore 'vendor' classes; if a dependency is used, assume the agent knows the public API of that package."
- "When analyzing routes, prioritize 'routes/api.php'."
