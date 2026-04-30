<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends AuthController
{
    public function __invoke()
    {
        $title = 'Manajemen Permission';
        $vue = "<permission-page :title='".json_encode($title)."' />";

        return view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        $data = Permission::query()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
            )
            ->paginate($this->per_page());

        return response()->json(['models' => $data]);
    }

    public function write(Request $request)
    {
        if ($request->req === 'delete') {
            $permission = Permission::find($request->id);

            $permission->delete();
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return response()->json(['status' => 'deleted']);
        }

        $request->validate([
            'name' => 'required|string|unique:permissions,name,'.$request->id,
        ]);

        Permission::updateOrCreate(
            ['id' => $request->id],
            [
                'name' => $request->name,
                'description' => $request->description,
            ]
        );

        return response()->json(true);
    }
}