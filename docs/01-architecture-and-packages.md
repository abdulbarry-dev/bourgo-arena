# Architecture and Packages

## System Architecture

The Bourgo Arena application is built on a modern, robust architecture leveraging the Laravel ecosystem to provide a high-performance sports facility management system. The application follows a monolithic approach for the admin dashboard while providing a comprehensive API layer for external consumers such as mobile applications.

### Core Architectural Patterns

- **Service-Oriented Architecture (SOA)**: The system encapsulates core business logic within dedicated Service classes. This separation of concerns ensures that complex logic, such as loyalty point calculations, reservation management, and payment processing, remains decoupled from the web and API entry points.
- **Data Transfer Objects (DTOs)**: The application utilizes structured DTOs to handle data flow between different layers. This ensures type safety and predictable data structures when processing complex requests like profile updates or reservation storage.
- **Repository Pattern**: Specific modules utilize the Repository pattern to abstract data access logic, promoting testability and allowing for easier transitions between different data storage implementations if needed.
- **TALL Stack (Admin Dashboard)**: The administrative backend is built using the TALL stack, which combines Tailwind CSS, Alpine.js, Laravel, and Livewire. This allows for a reactive, modern user interface without the complexity of a full-client-side JavaScript framework.
- **Event-Driven Communication**: The system uses Laravel's event and listener system to handle side effects such as sending notifications, generating tournament brackets, and auditing payments. Real-time updates are pushed to the frontend via WebSockets using Laravel Reverb.

---

## Backend Packages

### Core Framework and Infrastructure

- **Laravel Framework (v13)**: Serves as the foundational PHP framework, providing essential services such as routing, database abstraction, queue management, and dependency injection.
- **Laravel Fortify (v1)**: Acts as the headless authentication backend, providing secure implementations for user registration, login, two-factor authentication, and password resets.
- **Laravel Sanctum (v4)**: Provides a lightweight authentication system for APIs and SPAs using mobile tokens or session cookies, ensuring secure access to the application's resources.
- **Redis (via Predis v2)**: Employed for high-performance caching and as a robust queue driver to handle background tasks like notification dispatch and PDF generation.

### Business Logic and Utilities

- **Spatie Laravel Translatable (v6)**: Enables multilingual support for Eloquent models, allowing the application to store and retrieve content in multiple languages, such as French, seamlessly.
- **Laravel Tinker (v3)**: Provides a powerful REPL environment for interacting with the application's logic and database during development and debugging.
- **ArielMejiaDev Larapex Charts**: Used to generate dynamic, interactive charts within the admin dashboard to visualize key performance indicators and tournament statistics.
- **Barryvdh Laravel DOMPDF (v3)**: Facilitates the generation of professional PDF documents, such as payment receipts and membership reports.

---

## Frontend Packages

### UI Framework and Styling

- **Tailwind CSS (v4)**: A utility-first CSS framework used to build highly custom and responsive user interfaces with minimal custom CSS.
- **Livewire Flux (v2)**: A comprehensive UI component library specifically designed for Livewire, providing accessible and polished components like modals, buttons, and form inputs.
- **Alpine.js**: A lightweight JavaScript framework used for client-side interactivity and state management within the Blade templates, complementing Livewire's server-side reactivity.

### Build and Development Tools

- **Vite (v8)**: The modern frontend build tool that provides fast hot-module replacement and optimized asset bundling for production.
- **Laravel Vite Plugin (v3)**: Bridges the gap between Laravel and Vite, ensuring seamless integration of frontend assets into the PHP templates.
- **Autoprefixer**: Automatically handles CSS vendor prefixing to ensure cross-browser compatibility of the Tailwind-generated styles.

---

## Development and Quality Assurance

- **Pest PHP (v4)**: The primary testing framework used for writing expressive and maintainable feature and unit tests.
- **Laravel Pint (v1)**: An opinionated PHP code style fixer that ensures the codebase remains consistent and adheres to modern standards.
- **Laravel Sail (v1)**: A Docker-based development environment that provides a consistent setup for all developers across different operating systems.
- **Laravel Boost (v2)**: Provides specialized tools and utilities for development, including database schema inspection and documentation search.
- **Laravel Pail (v1)**: A utility for tailing application logs directly from the command line, facilitating rapid debugging.
