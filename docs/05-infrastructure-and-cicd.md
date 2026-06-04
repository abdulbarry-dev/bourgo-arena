# Infrastructure and CI/CD

## Deployment Architecture

The Bourgo Arena application is designed for a robust, multi-environment deployment strategy, ensuring high availability and seamless updates for both the administrative dashboard and the mobile API.

### Environment Management

- **Development**: Local development is facilitated by Laravel Sail, providing a consistent Docker-based environment that mirrors production services.
- **Staging**: A dedicated staging environment exists for integration testing and pre-production validation. Deployments to staging are automated and triggered by every push to the core development branch.
- **Production**: The production environment is isolated and requires manual triggering for deployments, ensuring that only verified releases are promoted to the live system.

---

## CI/CD Pipelines

The project utilizes GitHub Actions to automate the software development lifecycle, from code quality checks to production deployment.

### Quality Assurance Workflows

- **Automated Testing**: The system runs a comprehensive test suite using Pest PHP across multiple PHP versions (8.3, 8.4, 8.5) on every push and pull request. This ensures cross-version compatibility and prevents regressions.
- **Static Analysis and Linting**: A dedicated workflow executes Laravel Pint to enforce consistent code styling and quality across the entire PHP codebase.
- **Asset Verification**: Every CI run includes a frontend build step to ensure that Tailwind CSS and Vite configurations are valid and that assets can be correctly compiled.

### Deployment Workflows

- **Staging Deployment**: Triggered automatically upon successful code merges into the development branch. The workflow performs a full verification of the code (linting and testing) before deploying to the staging server via SSH.
- **Production Deployment**: A dispatch-based workflow that allows administrators to deploy specific Git tags or commit SHAs. It includes a mandatory verification phase where the entire test suite and build process must pass before the deployment to the production server is initiated.
- **Post-Deployment Tasks**: Both pipelines handle critical maintenance tasks automatically, including database migrations, optimization of configuration and route caches, and frontend asset rebuilding.
- **Notification System**: The pipelines are integrated with an SMTP notification system that alerts the development team of the deployment status (success or failure) and provides links to rollback guides in case of issues.

---

## Infrastructure Requirements

### Backend Infrastructure

- **PHP 8.3+**: The application leverages modern PHP features, including constructor property promotion and advanced type hinting.
- **PostgreSQL**: Used as the primary relational database for production, handling complex queries for reservations, tournaments, and member subscriptions.
- **Redis**: Acts as the high-performance caching layer and handles the enqueued background jobs for notifications and report generation.
- **SMTP Gateway**: Required for dispatching critical communications, including OTP codes, password resets, and subscription receipts.

### Storage and Assets

- **Private Storage Disk**: Used for sensitive generated assets like PDF payment receipts, ensuring they are not publicly accessible.
- **Public Assets**: Managed via Vite and served through the Laravel public directory, optimized for rapid loading on both web and mobile devices.

### Third-Party Integrations

- **Payment Gateways**: Production infrastructure requires secure connectivity to Konnect and Flouci APIs, managed through environment-specific API keys and webhook secrets.
- **Push Notification Providers**: Integrated for real-time mobile alerts, requiring correctly configured device tokens and provider credentials.
