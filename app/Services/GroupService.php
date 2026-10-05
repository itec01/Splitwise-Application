<?php

namespace App\Services;

use App\Exceptions\GroupNotFoundException;
use App\Exceptions\UnauthorizedGroupAccessException;
use App\Exceptions\MemberExistsException;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GroupService
{
    /**
     * Create a new group.
     */
    public function createGroup(array $data, User $user): Group
    {
        return Group::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'owner_id' => (string) $user->getKey(),
            'member_ids' => [
                (string) $user->getKey(),
            ],
        ]);
    }

    /**
     * Get groups belonging to the authenticated user.
     */
    public function getUserGroups(User $user)
    {
        $userId = (string) $user->getKey();

        return Group::where('member_ids', $userId)
            ->latest()
            ->get();
    }

    /**
     * Find a group.
     */
    public function findGroup(string $groupId): Group
    {
        $group = Group::find($groupId);

        if (!$group) {
            throw new GroupNotFoundException();
        }

        return $group;
    }

    /**
     * Ensure user is a group member.
     */
    public function ensureMember(Group $group, User $user): void
    {
        $userId = (string) $user->getKey();

        if (!in_array($userId, $group->member_ids ?? [], true)) {
            throw new UnauthorizedGroupAccessException(
                'You are not a member of this group.'
            );
        }
    }

    /**
     * Ensure user is group owner.
     */
    public function ensureOwner(Group $group, User $user): void
    {
        $userId = (string) $user->getKey();
        

        if ((string) $group->owner_id !== $userId) {
            throw new UnauthorizedGroupAccessException(
                'Only the group owner can perform this action.'
            );
        }
    }

    /**
     * Update group.
     */
    public function updateGroup(
        Group $group,
        array $data,
        User $user
    ): Group {
        $this->ensureOwner($group, $user);

        $group->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        return $group->fresh();
    }

    /**
     * Delete group.
     */
    public function deleteGroup(
        Group $group,
        User $user
    ): void {
        $this->ensureOwner($group, $user);

        $group->delete();

        Log::info('Group deleted', [
            'group_id' => (string) $group->getKey(),
            'owner_id' => (string) $user->getKey(),
        ]);
    }

    /**
     * Add member.
     */
    public function addMember(
        Group $group,
        User $user,
        string $memberId
    ): Group {
        $this->ensureOwner($group, $user);

        $member = User::find($memberId);

        if (!$member) {
            throw new \InvalidArgumentException(
                'The selected user does not exist.'
            );
        }

        $memberIds = $group->member_ids ?? [];
       // echo $memberIds;exit();

        $memberId = (string) $member->getKey();

        if (in_array($memberId, $memberIds, true)) {
            throw new MemberExistsException();
        }

        $memberIds[] = $memberId;

        $group->update([
            'member_ids' => array_values($memberIds),
        ]);

        
         
        return $group->fresh();
    }

    /**
     * Remove member.
     */
    public function removeMember(
        Group $group,
        User $user,
        string $memberId
    ): Group {
        $this->ensureOwner($group, $user);

        if ((string) $group->owner_id === $memberId) {
            throw new \InvalidArgumentException(
                'The group owner cannot be removed.'
            );
        }

        $memberIds = $group->member_ids ?? [];

        if (!in_array($memberId, $memberIds, true)) {
            throw new \InvalidArgumentException(
                'User is not a member of this group.'
            );
        }

        $memberIds = array_values(
            array_filter(
                $memberIds,
                fn ($id) => (string) $id !== $memberId
            )
        );

        $group->update([
            'member_ids' => $memberIds,
        ]);

        return $group->fresh();
    }

    /**
     * Get group members.
     */
    public function getMembers(
        Group $group,
        User $user
    ) {
        $this->ensureMember($group, $user);

        $memberIds = $group->member_ids ?? [];

        return User::whereIn('_id', $memberIds)->get();
    }
}