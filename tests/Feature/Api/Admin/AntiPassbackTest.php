<?php

namespace Tests\Feature\Api\Admin;

use App\Models\AdminAlert;
use App\Models\HikvisionTerminal;
use App\Models\Member;
use App\Models\NfcCard;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AntiPassbackTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected HikvisionTerminal $entryTerminal;

    protected HikvisionTerminal $exitTerminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $this->entryTerminal = HikvisionTerminal::factory()->create([
            'terminal_type' => 'entry',
            'name' => 'Main Entry',
        ]);

        $this->exitTerminal = HikvisionTerminal::factory()->create([
            'terminal_type' => 'exit',
            'name' => 'Main Exit',
        ]);
    }

    public function test_detects_entry_entry_violation(): void
    {
        $member = Member::factory()->create();
        NfcCard::factory()->create([
            'member_id' => $member->id,
            'uid' => 'CARD123',
            'status' => 'active',
        ]);

        // 1. Valid Entry
        $this->postJson(route('api.terminals.checkin'), [
            'card_uid' => 'CARD123',
            'terminal_id' => $this->entryTerminal->id,
            'result' => 'authorized',
        ], [
            'X-Terminal-Token' => $this->entryTerminal->api_token,
        ])->assertStatus(200)
            ->assertJsonPath('data.is_suspicious', false);

        // 2. Immediate second Entry (Violation)
        $this->postJson(route('api.terminals.checkin'), [
            'card_uid' => 'CARD123',
            'terminal_id' => $this->entryTerminal->id,
            'result' => 'authorized',
        ], [
            'X-Terminal-Token' => $this->entryTerminal->api_token,
        ])->assertStatus(200)
            ->assertJsonPath('data.is_suspicious', true);

        // Verify alert was generated
        $this->assertDatabaseHas('admin_alerts', [
            'member_id' => $member->id,
            'alert_type' => 'PASSBACK_VIOLATION',
        ]);
    }

    public function test_valid_entry_exit_flow(): void
    {
        $member = Member::factory()->create();
        NfcCard::factory()->create([
            'member_id' => $member->id,
            'uid' => 'CARD456',
            'status' => 'active',
        ]);

        // 1. Entry
        $this->postJson(route('api.terminals.checkin'), [
            'card_uid' => 'CARD456',
            'terminal_id' => $this->entryTerminal->id,
            'result' => 'authorized',
        ], [
            'X-Terminal-Token' => $this->entryTerminal->api_token,
        ])->assertStatus(200)
            ->assertJsonPath('data.is_suspicious', false);

        // 2. Exit
        $this->postJson(route('api.terminals.checkin'), [
            'card_uid' => 'CARD456',
            'terminal_id' => $this->exitTerminal->id,
            'result' => 'authorized',
        ], [
            'X-Terminal-Token' => $this->exitTerminal->api_token,
        ])->assertStatus(200)
            ->assertJsonPath('data.is_suspicious', false);
    }

    public function test_detects_exit_without_entry(): void
    {
        $member = Member::factory()->create();
        NfcCard::factory()->create([
            'member_id' => $member->id,
            'uid' => 'CARD789',
            'status' => 'active',
        ]);

        // 1. Exit without prior entry
        $this->postJson(route('api.terminals.checkin'), [
            'card_uid' => 'CARD789',
            'terminal_id' => $this->exitTerminal->id,
            'result' => 'authorized',
        ], [
            'X-Terminal-Token' => $this->exitTerminal->api_token,
        ])->assertStatus(200)
            ->assertJsonPath('data.is_suspicious', true);
    }

    public function test_admin_can_list_and_dismiss_alerts(): void
    {
        // Generate an alert
        $member = Member::factory()->create();
        NfcCard::factory()->create([
            'member_id' => $member->id,
            'uid' => 'CARD_ALERT',
            'status' => 'active',
        ]);

        $this->postJson(route('api.terminals.checkin'), [
            'card_uid' => 'CARD_ALERT',
            'terminal_id' => $this->exitTerminal->id,
            'result' => 'authorized',
        ], [
            'X-Terminal-Token' => $this->exitTerminal->api_token,
        ]);

        $alert = AdminAlert::first();

        // List alerts
        $this->actingAs($this->admin)
            ->getJson(route('api.admin.alerts.index'))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Dismiss alert
        $this->actingAs($this->admin)
            ->postJson(route('api.admin.alerts.dismiss', $alert))
            ->assertStatus(200);

        $this->assertTrue($alert->fresh()->is_dismissed);
    }

    public function test_admin_can_escalate_alert(): void
    {
        $member = Member::factory()->create();
        $alert = AdminAlert::create([
            'member_id' => $member->id,
            'terminal_id' => $this->entryTerminal->id,
            'alert_type' => 'PASSBACK_VIOLATION',
            'description' => 'Suspicious activity',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('api.admin.alerts.escalate', $alert))
            ->assertStatus(200)
            ->assertJsonPath('message', 'Alert escalated to management.');
    }
}
