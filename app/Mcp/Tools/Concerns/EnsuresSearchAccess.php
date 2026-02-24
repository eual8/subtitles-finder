<?php

namespace App\Mcp\Tools\Concerns;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

trait EnsuresSearchAccess
{
    protected function denyUnlessCanSearch(Request $request): ?Response
    {
        $user = $request->user();

        if ($user === null) {
            return Response::error('Authentication required. Use a valid token or authenticated session.');
        }

        if (! $user->can('admin.search.index')) {
            return Response::error('Access denied. Missing permission: admin.search.index.');
        }

        return null;
    }

    public function shouldRegister(Request $request): bool
    {
        return $request->user()?->can('admin.search.index') ?? false;
    }
}
