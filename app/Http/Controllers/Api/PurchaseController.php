<?php

namespace App\Http\Controllers\Api;

use App\Actions\ProcessPurchase;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequest;
use Illuminate\Http\JsonResponse;

class PurchaseController extends Controller
{
    public function __construct(private readonly ProcessPurchase $processPurchase) {}

    public function store(PurchaseRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->processPurchase->handle(
            cardNumber: $validated['card_number'],
            machineId: $validated['machine_id'],
            slotNumber: $validated['slot_number'],
            productPrice: $validated['product_price'],
        );

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }
}
