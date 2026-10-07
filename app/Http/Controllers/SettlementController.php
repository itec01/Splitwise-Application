<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSettlementRequest;
use App\Http\Requests\UpdateSettlementRequest;
use App\Http\Resources\SettlementResource;
use App\Models\Group;
use App\Models\Settlement;
use App\Models\User;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function __construct(
        protected SettlementService $settlementService
    ) {}

    /**
     * Create a settlement.
     */
    public function store(
        CreateSettlementRequest $request,
        string $group
    ): JsonResponse {
        $groupModel = $request->attributes->get('group');

        $paidby = User::find($request->validated('paid_by'));

        if (! $paidby) {
            return response()->json([
                'success' => false,
                'message' => 'PaidBy User not found.',
                'errors' => null,
            ], 404);
        }

        $settlement = $this->settlementService->createSettlement(
            $groupModel,
            $request->validated(),
            $paidby
        );

        return response()->json([
            'success' => true,
            'message' => 'Settlement created successfully.',
            'data' => new SettlementResource($settlement),
        ], 201);
    }

    /**
     * List a group's settlements.
     */
    public function index(
        Request $request,
        string $group
    ): JsonResponse {
        $groupModel = $request->attributes->get('group');

        $settlements = $this->settlementService->getGroupSettlements(
            $groupModel,
            $request->user(),
            (int) $request->query('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'message' => 'Settlement history retrieved successfully.',
            'data' => SettlementResource::collection($settlements),
        ]);
    }

    /**
     * Update a settlement.
     */
    public function update(
        UpdateSettlementRequest $request,
        string $group,
        string $settlement
    ): JsonResponse {
        $groupModel = $request->attributes->get('group');
        $settlementModel = $request->attributes->get('settlement');

        $updated = $this->settlementService->updateSettlement(
            $groupModel,
            $settlementModel,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Settlement updated successfully.',
            'data' => new SettlementResource($updated),
        ]);
    }

    /**
     * Delete a settlement.
     */
    public function destroy(
        Request $request,
        string $group,
        string $settlement
    ): JsonResponse {
        $groupModel = $request->attributes->get('group');
        $settlementModel = $request->attributes->get('settlement');

        $this->settlementService->deleteSettlement(
            $groupModel,
            $settlementModel,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Settlement deleted successfully.',
            'data' => null,
        ]);
    }
}
