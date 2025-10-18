<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Production;

class ProductionPolicy
{
    // List / view
    public function viewAny(User $user): bool { return in_array($user->role, ['Admin','Employee']); }
    public function view(User $user, Production $production): bool { return $this->viewAny($user); }

    // Create / edit (Employees can record production; Admin can do all)
    public function create(User $user): bool { return in_array($user->role, ['Admin','Employee']); }
    public function update(User $user, Production $production): bool { return in_array($user->role, ['Admin','Employee']); }

    // Dangerous operations — Admin only
    public function delete(User $user, Production $production): bool { return $user->role === 'Admin'; }
    public function restore(User $user, Production $production): bool { return $user->role === 'Admin'; }
    public function forceDelete(User $user, Production $production): bool { return $user->role === 'Admin'; }

    // Custom abilities
    public function export(User $user): bool { return $user->role === 'Admin'; }
    public function print(User $user, Production $production): bool { return $user->role === 'Admin'; }
}
