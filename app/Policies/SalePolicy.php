<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Sale;

class SalePolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['Admin','Employee']); }
    public function view(User $user, Sale $sale): bool { return $this->viewAny($user); }

    // Employees can record sales; Admin can do all
    public function create(User $user): bool { return in_array($user->role, ['Admin','Employee']); }
    public function update(User $user, Sale $sale): bool { return $user->role === 'Admin'; } // optional: lock edits for employees
    public function delete(User $user, Sale $sale): bool { return $user->role === 'Admin'; }

    // Extra ops
    public function print(User $user, Sale $sale): bool { return $user->role === 'Admin'; }
    public function refund(User $user, Sale $sale): bool { return $user->role === 'Admin'; }
    public function export(User $user): bool { return $user->role === 'Admin'; }
}
