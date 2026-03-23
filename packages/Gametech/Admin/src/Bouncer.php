<?php

namespace Gametech\Admin;

use Illuminate\Support\Facades\Auth;

class Bouncer
{
    /**
     * Checks if user allowed or not for certain action
     *
     * @param  string  $permission
     * @return void
     */
    public function hasPermission($permission)
    {
        if (!Auth::guard('admin')->check()) {
            return false;
        }

        $admin = Auth::guard('admin')->user();
        if ((string) ($admin->superadmin ?? 'N') === 'Y') {
            return true;
        }

        if ($admin->role->permission_type == 'all') {
            return true;
        }

        return $admin->hasPermission($permission);
    }

    /**
     * Checks if user allowed or not for certain action
     *
     * @param  string  $permission
     * @return void
     */
    public static function allow($permission)
    {
        if (!Auth::guard('admin')->check()) {
            abort(401, 'This action is unauthorized');
        }

        $admin = Auth::guard('admin')->user();
        if ((string) ($admin->superadmin ?? 'N') === 'Y') {
            return;
        }

        if (!$admin->hasPermission($permission)) {
            abort(401, 'This action is unauthorized');
        }
    }
}
