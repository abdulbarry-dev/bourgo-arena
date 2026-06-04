# Bourgo Arena

Bourgo Arena is a comprehensive, modern sports facility management system designed to streamline the administration of complex sports clubs. The platform provides a powerful administrative dashboard for facility managers and a robust API layer for mobile application integration, handling everything from member onboarding and recurring subscriptions to complex tournament bracket logistics and multi-gateway payment processing.

---

## Key Features

- **Administrative Dashboard**: A highly reactive TALL stack interface for real-time facility management.
- **Member & Family Management**: Recursive family account structures with designating heads of families and managed child accounts.
- **Subscription Engine**: Automated handling of membership plans, renewals, and expiration logic.
- **Facility Reservations**: Concurrency-safe booking system for activities and court slots with dynamic pricing.
- **Tournament Logistics**: Automated single-elimination bracket generation, match tracking, and winner advancement.
- **Loyalty Program**: A rules-based points system that rewards members for participation and renewals.
- **Payment Integration**: Seamless processing through multiple gateways (Konnect, Flouci) with automated PDF receipt generation.
- **Multi-Channel Notifications**: Real-time alerts via Email, SMS, and Push Notifications.

---

## Technical Stack

- **Backend**: Laravel 13 (PHP 8.3+), Fortify, Sanctum, PostgreSQL, Redis.
- **Frontend**: Livewire 4, Alpine.js, Flux UI, Tailwind CSS 4.
- **Infrastructure**: Docker (Laravel Sail), GitHub Actions CI/CD.
- **Testing**: Pest PHP, Laravel Pint for code styling.

---

## Project Documentation

For detailed technical information, please refer to the granular documentation in the `docs/` directory:

1.  **[Architecture and Packages](docs/01-architecture-and-packages.md)**: Deep dive into the system's foundation, core patterns, and full dependency list.
2.  **[Database and Models](docs/02-database-and-models.md)**: Detailed schema analysis, model relationships, and custom business logic.
3.  **[API and Services](docs/03-api-and-services.md)**: Complete guide to the RESTful API endpoints, service layer orchestration, and background tasks.
4.  **[Admin Dashboard](docs/04-admin-dashboard.md)**: Overview of the TALL stack interface, UI components, and administrative workflows.
5.  **[Infrastructure and CI/CD](docs/05-infrastructure-and-cicd.md)**: Documentation of the deployment architecture, testing pipelines, and infrastructure requirements.

---

## Getting Started

### Prerequisites

- PHP 8.3 or higher
- Composer
- Node.js & NPM
- Docker (optional, but recommended for Sail)

### Installation

1.  **Clone the repository**:
    ```bash
    git clone https://github.com/your-repo/bourgo-arena.git
    cd bourgo-arena
    ```

2.  **Setup the environment**:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

3.  **Install dependencies**:
    ```bash
    composer install
    npm install
    ```

4.  **Launch the development environment**:
    If using Laravel Sail:
    ```bash
    ./vendor/bin/sail up -d
    ```

5.  **Run migrations and seeders**:
    ```bash
    php artisan migrate --seed
    ```

6.  **Build frontend assets**:
    ```bash
    npm run build
    # or for development:
    npm run dev
    ```

### Running Tests

To ensure everything is set up correctly, run the test suite:
```bash
php artisan test
```

---

## Contributing

Please ensure that all new code follows the project's coding standards by running the linter before submitting a pull request:
```bash
composer lint
```
