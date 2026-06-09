<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserPaymentController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the user's payments.
     *
     * @return AnonymousResourceCollection<PaymentResource>
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $payments = Payment::where('member_id', $request->user()->id)
            ->with(['subscription.plan', 'reservation.activity'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($payments, PaymentResource::class);
    }
}
