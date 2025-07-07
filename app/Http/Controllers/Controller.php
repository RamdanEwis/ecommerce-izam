<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Get the authenticated user ID.
     *
     * @return int|null
     */
    protected function getAuthUserId(): ?int
    {
        return Auth::id();
    }

    /**
     * Get the authenticated user.
     *
     * @return \App\Models\User|null
     */
    protected function getAuthUser()
    {
        return Auth::user();
    }
}
