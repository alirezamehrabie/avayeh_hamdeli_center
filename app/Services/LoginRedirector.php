<?php

namespace App\Services;

use App\Models\User;

class LoginRedirector
{
    public function pathFor(User $user): string
    {
        $redirects = [
            ['ability' => 'access-child-supporter-panel', 'route' => 'child-supporter.dashboard'],
            ['ability' => 'access-admin-panel', 'route' => 'admin.dashboard'],
            ['ability' => 'access-social-worker-panel', 'route' => 'social-worker.dashboard'],
            ['ability' => 'access-distribution-operator-panel', 'route' => 'distribution-operator.define-service'],
        ];

        foreach ($redirects as $redirect) {
            if ($user->can($redirect['ability'])) {
                return route($redirect['route']);
            }
        }

        return '/';
    }
}
