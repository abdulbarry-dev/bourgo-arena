<?php

use App\Models\User;

it('handles 50 rapid sequential terminal registrations', function () {
    $admin = User::factory()->admin()->create();

    for ($i = 0; $i < 50; $i++) {
        $response = $this->actingAs($admin)->postJson('/api/terminal-provisioning', [
            'name' => "Terminal {$i}",
            'ip_address' => "10.0.0." . ($i + 1),
            'serial_number' => "HKV-TERM-LOAD-{$i}",
            'location' => 'Load Test Location',
            'terminal_type' => 'entry',
        ]);
        
        $response->assertCreated();
    }
    
    $this->assertDatabaseCount('hikvision_terminals', 50);
});
