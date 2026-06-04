<?php

use Illuminate\Support\Facades\Schema;

test('reservation system migrations create the expected tables and indexes', function () {
    expect(Schema::hasTable('reservations'))->toBeTrue();
    expect(Schema::hasTable('activity_slots'))->toBeTrue();
    expect(Schema::hasTable('activity_time_slots'))->toBeTrue();
    expect(Schema::hasTable('payment_transactions'))->toBeTrue();

    foreach ([
        'reservations' => [
            'user_id',
            'activity_id',
            'activity_time_slot_id',
            'reservation_status',
            'payment_status',
            'deposit_amount',
            'full_amount',
            'payment_gateway',
            'transaction_reference',
            'cancellation_reason',
        ],
        'activity_slots' => [
            'activity_id',
            'starts_at',
            'ends_at',
            'capacity',
            'booked_count',
            'is_available',
        ],
        'activity_time_slots' => [
            'activity_id',
            'start_time',
            'end_time',
            'max_capacity',
            'reserved_count',
            'is_available',
        ],
        'payment_transactions' => [
            'transaction_id',
            'user_id',
            'reservation_id',
            'amount',
            'currency',
            'payment_gateway',
            'transaction_status',
            'external_gateway_reference',
            'reservation_details',
            'user_information',
            'ip_address',
            'user_agent',
            'request_payload',
            'response_payload',
        ],
    ] as $table => $columns) {
        foreach ($columns as $column) {
            expect(Schema::hasColumn($table, $column))->toBeTrue();
        }
    }

    expect(collect(Schema::getIndexes('reservations'))->pluck('name')->reject(fn (string $name) => $name === 'primary')->values()->all())->toEqualCanonicalizing([
        'reservations_user_id_reservation_status_index',
        'reservations_activity_id_activity_time_slot_id_index',
        'reservations_user_slot_unique',
        'reservations_payment_status_payment_gateway_index',
        'reservations_transaction_reference_index',
    ]);

    expect(collect(Schema::getIndexes('activity_slots'))->pluck('name')->reject(fn (string $name) => $name === 'primary')->values()->all())->toEqualCanonicalizing([
        'activity_slots_unique_range',
        'activity_slots_activity_id_is_available_starts_at_index',
    ]);

    expect(collect(Schema::getIndexes('activity_time_slots'))->pluck('name')->reject(fn (string $name) => $name === 'primary')->values()->all())->toEqualCanonicalizing([
        'activity_time_slots_unique_slot',
        'activity_time_slots_activity_id_is_available_start_time_index',
    ]);

    expect(collect(Schema::getIndexes('payment_transactions'))->pluck('name')->reject(fn (string $name) => $name === 'primary')->values()->all())->toEqualCanonicalizing([
        'payment_transactions_transaction_id_unique',
        'payment_transactions_user_id_created_at_index',
        'payment_transactions_user_id_payment_gateway_index',
        'payment_transactions_gateway_status_index',
        'payment_transactions_reservation_created_at_index',
        'payment_transactions_external_gateway_reference_index',
    ]);
});
