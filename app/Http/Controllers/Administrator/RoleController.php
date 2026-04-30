<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\ApplicationMenu;

class RoleController extends AuthController
{
    public function __invoke()
    {
        $constant = [
            'menus' => ApplicationMenu::with('permissions')->where('is_active', true)->get()
        ];

        $title = 'Manajemen Role';
        $vue = "<role-page :title='" . json_encode($title) . "' :constant='" . json_encode($constant) . "' />";
        return view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req === 'table') {
            $roles = Role::with('permissions')
                ->when($request->search, fn ($q) =>
                    $q->where('name', 'like', "%{$request->search}%")
                )
                ->paginate($this->per_page());

            return response()->json(['models' => $roles]);
        }

        if ($request->req === 'permission_data') {
            $role = Role::findOrFail($request->id);

            return response()->json([
                'models' => $role->permissions()->select('id', 'name')->get(),
                'available_permissions' => Permission::whereNotIn(
                    'id',
                    $role->permissions->pluck('id')
                )->select('id', 'name')->get()
            ]);
        }
    }

    public function write(Request $request)
    {
        if ($request->req === 'write') {
            $request->validate([
                'name' => 'required|string|unique:roles,name,' . $request->id,
            ]);

            $role = Role::updateOrCreate(
                ['id' => $request->id],
                [
                    'name' => $request->name,
                ]
            );

            $role->syncPermissions($request->permissions ?? []);

            return response()->json(true);
        }

        if ($request->req === 'attach_permission') {
            $role = Role::find($request->id);

            $role->givePermissionTo($request->permissions);
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            return response()->json(true);
        }

        if ($request->req === 'detach_permission') {
            $role = Role::find($request->id);

            $role->revokePermissionTo($request->permissions);
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            return response()->json(true);
        }
    }
}