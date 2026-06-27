<?php

namespace App\Services;

use App\Models\User;

class LoginRedirector
{
    /**
     * @var list<array{ability: string, route: string}>
     */
    private array $redirects = [
        ['ability' => 'access-child-supporter-panel', 'route' => 'child-supporter.dashboard'],
        ['ability' => 'access-admin-panel', 'route' => 'admin.dashboard'],
        ['ability' => 'access-social-worker-panel', 'route' => 'social-worker.dashboard'],
        ['ability' => 'access-distribution-operator-panel', 'route' => 'distribution-operator.define-service'],
    ];

    public function pathFor(User $user): string
    {
        foreach ($this->redirects as $redirect) {
            if ($user->can($redirect['ability'])) {
                return route($redirect['route']);
            }
        }

        return '/';
    }

    public function hasAuthorizedPanel(User $user): bool
    {
        foreach ($this->redirects as $redirect) {
            if ($user->can($redirect['ability'])) {
                return true;
            }
        }

        return false;
    }
}
