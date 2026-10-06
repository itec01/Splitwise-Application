<?php
namespace App\http\Controllers;

use App\Http\Requests\AddGroupMemberRequest;
use App\Http\Requests\CreateGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Http\Resources\GroupResource;
use App\Services\BalanceService;
use App\Services\GroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct(
        protected GroupService $groupService
    ) {
    }

    /**
     * Create group.
     */
    public function store(CreateGroupRequest $request): JsonResponse
    {
        $group = $this->groupService->createGroup(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully.',
            'data' => new GroupResource($group),
        ], 201);
    }

    /**
     * Get authenticated user's groups.
     */
    public function index(Request $request): JsonResponse
    {
        $groups = $this->groupService->getUserGroups(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Groups retrieved successfully.',
            'data' => GroupResource::collection($groups),
        ]);
    }

    /**
     * Get group details.
     */
    public function show(
        Request $request,
        string $group
    ): JsonResponse {
        $groupModel = $this->groupService->findGroup($group);

        $this->groupService->ensureMember(
            $groupModel,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Group retrieved successfully.',
            'data' => new GroupResource($groupModel),
        ]);
    }

    /**
     * Update group.
     */
    public function update(
        UpdateGroupRequest $request,
        string $group
    ): JsonResponse {
        $groupModel = $this->groupService->findGroup($group);

        $groupModel = $this->groupService->updateGroup(
            $groupModel,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Group updated successfully.',
            'data' => new GroupResource($groupModel),
        ]);
    }

    /**
     * Delete group.
     */
    public function destroy(
        Request $request,
        string $group
    ): JsonResponse {
        $groupModel = $this->groupService->findGroup($group);

        $this->groupService->deleteGroup(
            $groupModel,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Group deleted successfully.',
            'data' => null,
        ]);
    }

    /**
     * Get group members.
     */
    public function members(
        Request $request,
        string $group
    ): JsonResponse {
        $groupModel = $this->groupService->findGroup($group);

        $members = $this->groupService->getMembers(
            $groupModel,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Group members retrieved successfully.',
            'data' => $members,
        ]);
    }

    /**
     * Add member.
     */
    public function addMember(
        AddGroupMemberRequest $request,
        string $group
    ): JsonResponse {
        $groupModel = $this->groupService->findGroup($group);

        $groupModel = $this->groupService->addMember(
            $groupModel,
            $request->user(),
            $request->validated('user_id')
        );

        return response()->json([
            'success' => true,
            'message' => 'Member added successfully.',
            'data' => new GroupResource($groupModel),
        ]);
    }

    /**
     * Remove member.
     */
    public function removeMember(
        Request $request,
        string $group,
        string $user
    ): JsonResponse {
        $groupModel = $this->groupService->findGroup($group);

        $groupModel = $this->groupService->removeMember(
            $groupModel,
            $request->user(),
            $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Member removed successfully.',
            'data' => new GroupResource($groupModel),
        ]);
    }

    /**
     * Get group balances.
     */
    public function balances(
        Request $request,
        string $group
    ): JsonResponse {
        $groupModel = $this->groupService->findGroup($group);

        $this->groupService->ensureMember(
            $groupModel,
            $request->user()
        );

        $balances = app(BalanceService::class)
            ->getGroupBalances($groupModel);

        return response()->json([
            'success' => true,
            'message' => 'Group balances retrieved successfully.',
            'data' => [
                'group_id' => (string) $groupModel->getKey(),
                'balances' => $balances,
            ],
        ]);
    }
}
?>