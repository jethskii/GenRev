<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BatchAllocation;

class BatchAllocationPolicy
{
    /**
     * Determine whether the user can view any allocations.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('sales') || $user->hasRole('inventory');
    }

    /**
     * Determine whether the user can view a specific allocation.
     */
    public function view(User $user, BatchAllocation $allocation): bool
    {
        return $user->hasRole('admin')
            || $user->id === optional($allocation->orderItem->order)->user_id;
    }

    /**
     * Determine whether the user can create an allocation.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update an allocation (change batch, qty, etc).
     */
    public function update(User $user, BatchAllocation $allocation): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete an allocation.
     */
    public function delete(User $user, BatchAllocation $allocation): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can approve overrides to FIFO.
     */
    public function approveOverride(User $user, BatchAllocation $allocation): bool
    {
        return $user->hasRole('admin');
    }
}
