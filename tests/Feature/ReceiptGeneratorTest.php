<?php

namespace Tests\Feature;

use App\Services\ReceiptGenerator;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptGeneratorTest extends TestCase
{
    public function test_it_generates_and_stores_a_pdf_receipt(): void
    {
        Storage::fake('local');
        config(['payment.receipts.disk' => 'local']);

        $generator = app(ReceiptGenerator::class);

        $path = $generator->generate([
            'member_name' => 'Jane Doe',
            'plan_name' => 'Premium',
            'amount_paid' => 120.500,
            'payment_method' => 'konnect',
            'payment_reference' => 'TXN-1234',
            'paid_at' => now()->toDateTimeString(),
            'enrolled_by' => 'Manager User',
            'subscription_id' => 42,
        ]);

        Storage::disk('local')->assertExists($path);

        $content = Storage::disk('local')->get($path);
        $this->assertStringStartsWith('%PDF-', $content);
    }
}
