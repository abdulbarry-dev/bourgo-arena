<?php

/**
 * Authorization Configuration
 *
 * Defines the RBAC permission matrix for all resources and actions.
 * Format: resource => [action => [allowed_roles]]
 *
 * Roles: admin, manager
 */
return [
    'matrix' => [
        'members' => [
            'view' => ['admin', 'manager'],
            'view_all' => ['admin', 'manager'],
            'create' => ['admin'],
            'update' => ['admin', 'manager'],
            'suspend' => ['admin', 'manager'],
            'activate' => ['admin', 'manager'],
            'delete' => ['admin'],
            'reset_password' => ['admin', 'manager'],
        ],

        'subscriptions' => [
            'view' => ['admin', 'manager'],
            'view_all' => ['admin', 'manager'],
            'enroll' => ['admin', 'manager'],
            'suspend' => ['admin', 'manager'],
            'resume' => ['admin', 'manager'],
            'transfer' => ['admin'],
            'view_payment' => ['admin'],
        ],

        'nfc_cards' => [
            'view' => ['admin', 'manager'],
            'view_all' => ['admin', 'manager'],
            'assign' => ['admin', 'manager'],
            'suspend' => ['admin', 'manager'],
            'mark_lost' => ['admin', 'manager'],
            'reactivate' => ['admin', 'manager'],
        ],

        'terminals' => [
            'view' => ['admin', 'manager'],
            'view_all' => ['admin', 'manager'],
            'provision' => ['admin'],
            'revoke_token' => ['admin'],
            'decommission' => ['admin'],
            'view_logs' => ['admin', 'manager'],
        ],

        'check_in' => [
            'view_monitor' => ['admin', 'manager'],
            'view_audit_log' => ['admin', 'manager'],
            'export_events' => ['admin', 'manager'],
            'view_anti_passback_alerts' => ['admin', 'manager'],
            'dismiss_alert' => ['admin', 'manager'],
            'escalate_alert' => ['admin'],
        ],

        'analytics' => [
            'view_revenue' => ['admin'],
            'view_occupancy' => ['admin', 'manager'],
            'export_reports' => ['admin'],
        ],

        'scheduling' => [
            'view_courses' => ['admin', 'manager'],
            'create_course' => ['admin', 'manager'],
            'edit_course' => ['admin', 'manager'],
            'cancel_course' => ['admin', 'manager'],
            'manage_enrollments' => ['admin', 'manager'],
        ],

        'payments' => [
            'view' => ['admin'],
            'export' => ['admin'],
        ],

        'system' => [
            'access_dashboard' => ['admin', 'manager'],
            'view_logs' => ['admin'],
            'manage_backups' => ['admin'],
        ],
    ],

    /**
     * Detailed role descriptions for documentation.
     */
    'roles' => [
        'admin' => [
            'title' => 'Administrator',
            'description' => 'Full access to all resources, financial data, and system configuration.',
            'permissions' => 'All',
        ],
        'manager' => [
            'title' => 'Manager',
            'description' => 'Member management, card assignment, check-in monitoring. No financial access.',
            'permissions' => 'Members, Subscriptions (enroll/suspend/resume), NFC Cards, Check-in, Scheduling',
        ],
    ],
];
