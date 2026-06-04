# Admin Dashboard

## Interface Overview

The Bourgo Arena administrative dashboard is a highly reactive, single-page-like experience built using the TALL stack. It provides facility managers and administrators with real-time control over every aspect of the sports complex, from member onboarding to tournament bracket management.

### User Experience and Design

- **Reactive Components**: Powered by Livewire, the interface updates dynamically in response to user actions without full page reloads. This provides a fluid experience for complex tasks like filtering large member datasets or managing tournament rounds.
- **Flux UI Integration**: The dashboard utilizes the Flux UI component library to ensure a consistent, accessible, and professional aesthetic. Components like flyout panels, modals, and sophisticated form inputs are used throughout the application.
- **Responsive Management**: Built with Tailwind CSS, the dashboard is fully responsive, allowing managers to perform critical tasks from mobile devices or tablets while on the facility floor.
- **Client-Side Interactivity**: Alpine.js handles light-weight frontend states, such as toggling visibility of details, managing modal animations, and handling client-side event dispatching.

---

## Core Management Modules

### Member and Family Administration

The member management system is the heart of the administrative dashboard, providing a 360-degree view of the club's membership.

- **Centralized Member Table**: A sophisticated data grid featuring real-time search, multi-column sorting, and granular filtering by status, membership plan, and subscription validity.
- **Onboarding and Profile Management**: Admins can manually add members, trigger onboarding emails, and manage profile details through interactive flyout panels.
- **Family Account Controls**: A dedicated interface allows managers to link accounts, designate heads of families, and manage child members within a family group.
- **Account Actions**: Provides immediate controls for activating, suspending, or scheduling account deletions, with integrated authorization checks.
- **Data Portability**: Supports filtered CSV exports of the member database for external reporting and marketing purposes.

### Tournament and Event Logistics

The dashboard provides a robust suite of tools for managing competitive sports events and tournaments.

- **Event Lifecycle Management**: Managers can create and configure events, set registration deadlines, and track participant lists.
- **Automated Bracket Generation**: A specialized interface allows for the automated generation of single-elimination tournament brackets. The system calculates seeds and match pairings based on the participant list.
- **Real-Time Match Updates**: Admins can record match scores and advance winners through the bracket in real-time. The system automatically populates the next round of matches as results are confirmed.
- **Participant Communication**: Integrated tools allow for the immediate dispatch of bracket announcements and schedule changes via push notifications and emails.

### Financial Oversight and Auditing

The billing module provides transparency and control over all financial transactions within the system.

- **Payment Audit Trail**: A dedicated transaction log records every interaction with external payment gateways. Managers can inspect raw request and response payloads for debugging and reconciliation.
- **Subscription Management**: Tracks the lifecycle of member memberships, including enrollment, manual renewals, and status overrides.
- **Transaction Filtering**: Allows for detailed searching of payments by transaction ID, user information, gateway provider, and status.

### Facility and Service Configuration

Allows for the granular configuration of the facility's offerings.

- **Service and Catalog Management**: Admins can define high-level services (e.g., Padel, Football) and manage the catalog of activities, courses, and time slots.
- **Resource Allocation**: Provides tools to manage court availability, set pricing tiers, and define the features and descriptions for bookable activities.

---

## Interactive Workflows

### Flyout and Modal Patterns

The dashboard heavily utilizes "Flyout" panels for complex data entry and "Modals" for critical confirmations. These patterns keep the user in context, preventing the mental overhead of navigating between multiple pages. For example, editing a member's family structure happens in a slide-over panel that maintains the background state of the member list.

### Toast Notifications and Feedback

Real-time feedback is provided through an integrated toast notification system. Actions like saving a profile, generating a bracket, or processing a refund trigger immediate visual confirmation, ensuring the administrator is always aware of the system's state.

### Global Search and Navigation

A powerful global search service allows managers to rapidly locate members, activities, or transactions from anywhere in the dashboard, significantly reducing the time required for common administrative tasks.
