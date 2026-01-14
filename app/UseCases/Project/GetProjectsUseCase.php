<?php

namespace App\UseCases\Project;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GetProjectsUseCase
{
    public function execute(User $user): Collection
    {
        return $user->proects()
            ->with('users')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
