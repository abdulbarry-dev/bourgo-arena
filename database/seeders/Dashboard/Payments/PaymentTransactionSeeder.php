<?php

namespace Database\Seeders\Dashboard\Payments;

use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentTransactionSeeder extends Seeder
{
    public function run(): void
    {
        if (PaymentTransaction::count() > 5) {
            return;
        }

        $users = User::whereIn('role', ['admin', 'manager'])->get();
        if ($users->isEmpty()) {
            $users = User::factory()->count(3)->create();
        }

        $transactions = $this->buildTransactions($users);

        foreach ($transactions as $data) {
            PaymentTransaction::create($data);
        }
    }

    private function buildTransactions($users): array
    {
        $gateways = ['konnect', 'manual_admin'];
        $statuses = ['success', 'pending', 'failed'];
        $ips = ['192.168.1.100', '10.0.0.45', '172.16.0.88', '41.225.120.33', '197.27.84.12', '154.120.66.9'];
        $agents = [
            'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/120.0.6099.230 Mobile Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2) AppleWebKit/605.1.15 Mobile/15E148',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/121.0.6167.85 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2) AppleWebKit/605.1.15 Safari/604.1',
            'okhttp/4.12.0',
        ];

        $rows = [];

        // Konnect - success (6)
        foreach ([
            ['txn' => 'KNT-2026-0001', 'amount' => 89.000, 'days' => 45],
            ['txn' => 'KNT-2026-0003', 'amount' => 129.000, 'days' => 32],
            ['txn' => 'KNT-2026-0005', 'amount' => 349.000, 'days' => 28],
            ['txn' => 'KNT-2026-0007', 'amount' => 1199.000, 'days' => 14],
            ['txn' => 'KNT-2026-0009', 'amount' => 45.000, 'days' => 7],
            ['txn' => 'KNT-2026-0011', 'amount' => 75.000, 'days' => 2],
        ] as $r) {
            $rows[] = $this->makeTransaction($users, 'konnect', 'success', $r['amount'], $r['days'], $ips, $agents, $r['txn']);
        }

        // Konnect - pending (4)
        foreach ([
            ['txn' => 'KNT-2026-0013', 'amount' => 25.000, 'days' => 20],
            ['txn' => 'KNT-2026-0015', 'amount' => 150.000, 'days' => 15],
            ['txn' => 'KNT-2026-0017', 'amount' => 350.000, 'days' => 8],
            ['txn' => 'KNT-2026-0019', 'amount' => 89.000, 'days' => 3],
        ] as $r) {
            $rows[] = $this->makeTransaction($users, 'konnect', 'pending', $r['amount'], $r['days'], $ips, $agents, $r['txn']);
        }

        // Konnect - failed (5)
        foreach ([
            ['txn' => 'KNT-2026-0021', 'amount' => 10.000, 'days' => 40],
            ['txn' => 'KNT-2026-0023', 'amount' => 500.000, 'days' => 35],
            ['txn' => 'KNT-2026-0025', 'amount' => 65.000, 'days' => 22],
            ['txn' => 'KNT-2026-0027', 'amount' => 200.000, 'days' => 12],
            ['txn' => 'KNT-2026-0029', 'amount' => 30.000, 'days' => 5],
        ] as $r) {
            $rows[] = $this->makeTransaction($users, 'konnect', 'failed', $r['amount'], $r['days'], $ips, $agents, $r['txn']);
        }

        // Manual admin - success (6)
        foreach ([
            ['txn' => 'MAN-2026-0002', 'amount' => 30.000, 'days' => 50],
            ['txn' => 'MAN-2026-0004', 'amount' => 900.000, 'days' => 38],
            ['txn' => 'MAN-2026-0006', 'amount' => 75.000, 'days' => 25],
            ['txn' => 'MAN-2026-0008', 'amount' => 200.000, 'days' => 18],
            ['txn' => 'MAN-2026-0010', 'amount' => 45.000, 'days' => 10],
            ['txn' => 'MAN-2026-0012', 'amount' => 120.000, 'days' => 4],
        ] as $r) {
            $rows[] = $this->makeTransaction($users, 'manual_admin', 'success', $r['amount'], $r['days'], $ips, $agents, $r['txn']);
        }

        // Manual admin - pending (4)
        foreach ([
            ['txn' => 'MAN-2026-0014', 'amount' => 45.000, 'days' => 22],
            ['txn' => 'MAN-2026-0016', 'amount' => 200.000, 'days' => 16],
            ['txn' => 'MAN-2026-0018', 'amount' => 89.000, 'days' => 9],
            ['txn' => 'MAN-2026-0020', 'amount' => 150.000, 'days' => 6],
        ] as $r) {
            $rows[] = $this->makeTransaction($users, 'manual_admin', 'pending', $r['amount'], $r['days'], $ips, $agents, $r['txn']);
        }

        // Manual admin - failed (5)
        foreach ([
            ['txn' => 'MAN-2026-0022', 'amount' => 20.000, 'days' => 42],
            ['txn' => 'MAN-2026-0024', 'amount' => 150.000, 'days' => 30],
            ['txn' => 'MAN-2026-0026', 'amount' => 35.000, 'days' => 24],
            ['txn' => 'MAN-2026-0028', 'amount' => 100.000, 'days' => 11],
            ['txn' => 'MAN-2026-0030', 'amount' => 55.000, 'days' => 1],
        ] as $r) {
            $rows[] = $this->makeTransaction($users, 'manual_admin', 'failed', $r['amount'], $r['days'], $ips, $agents, $r['txn']);
        }

        return $rows;
    }

    private function makeTransaction($users, string $gateway, string $status, float $amount, int $daysAgo, array $ips, array $agents, string $txnId): array
    {
        $createdAt = now()->subDays($daysAgo);

        return [
            'transaction_id' => $txnId,
            'user_id' => $users->random()->id,
            'amount' => $amount,
            'payment_gateway' => $gateway,
            'transaction_status' => $status,
            'external_gateway_reference' => 'ext-'.strtolower($txnId),
            'ip_address' => $ips[array_rand($ips)],
            'user_agent' => $agents[array_rand($agents)],
            'request_payload' => [
                'amount' => $amount,
                'currency' => 'TND',
                'card_token' => 'ct_'.bin2hex(random_bytes(12)),
                'customer' => [
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'email' => fake()->safeEmail(),
                    'phone' => '+216'.fake()->numerify('########'),
                ],
                'metadata' => [
                    'source' => $gateway === 'konnect' ? 'mobile_app' : 'admin_dashboard',
                    'ip_country' => 'TN',
                ],
                'timestamp' => $createdAt->toIso8601String(),
            ],
            'response_payload' => match ($status) {
                'success' => [
                    'transaction_id' => $txnId,
                    'status' => 'completed',
                    'status_code' => '00',
                    'message' => 'Transaction approved successfully',
                    'processed_at' => $createdAt->addMinutes(2)->toIso8601String(),
                    'card_last_four' => (string) fake()->numerify('####'),
                ],
                'pending' => [
                    'transaction_id' => $txnId,
                    'status' => 'pending',
                    'status_code' => '03',
                    'message' => 'Transaction is pending processing',
                    'processed_at' => null,
                ],
                'failed' => [
                    'transaction_id' => $txnId,
                    'status' => 'failed',
                    'status_code' => '51',
                    'message' => match (rand(1, 3)) {
                        1 => 'Insufficient funds',
                        2 => 'Card declined by issuer',
                        default => 'Transaction timeout exceeded',
                    },
                    'error_code' => match (rand(1, 3)) {
                        1 => 'ERR_INSUFFICIENT_FUNDS',
                        2 => 'ERR_CARD_DECLINED',
                        default => 'ERR_TIMEOUT',
                    },
                    'processed_at' => $createdAt->addSeconds(30)->toIso8601String(),
                ],
            },
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
