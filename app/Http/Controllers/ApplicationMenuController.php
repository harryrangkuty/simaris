<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\ApplicationMenu;
use Spatie\Permission\Models\Permission;

class ApplicationMenuController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [
            'permissions' => Permission::select('id','name')->get(),
            'parentMenus' => ApplicationMenu::select('id','title')->get(),
        ];
        $title = 'Manajemen Menu Aplikasi';

        $vue = "<application-menu-page :title='" . json_encode($title) . "' :constant='" . json_encode($constant) . "' />";
        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req === 'table') {
            $query = ApplicationMenu::with(['parent','permissions']);

            if ($request->search) {
                $query->where('title', 'like', "%{$request->search}%");
            }

            $data = $query->paginate($this->per_page());

            return response()->json(['models' => $data]);
        }
    }

    public function write(Request $request)
    {
        $reqType = $request->req;
        if ($reqType === 'write') {
            $this->validate($request, [
                'title' => 'required|string|max:255',
                'key' => 'nullable|string|max:100|unique:application_menus,key,' . $request->id,
                'url' => 'nullable|string|max:255',
                'icon' => 'nullable|string|max:50',
                'order' => 'nullable|integer',
                'is_active' => 'required|boolean',
            ]);

            $menu = ApplicationMenu::find($request->id) ?? new ApplicationMenu();
            $menu->fill($request->only([
                'parent_id', 'key', 'title', 'url', 'icon', 'order', 'is_active'
            ]));
            $menu->save();

            $menu->permissions()->sync($request->permissions ?? []);

            return response()->json($menu);
        } elseif ($reqType === 'delete') {
            $menu = ApplicationMenu::find($request->id);
            if (!$menu) return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);

            $menu->delete();

            return response()->json(['status' => 'success', 'action' => 'delete', 'data' => $menu]);
        } elseif ($reqType === 'restore') {
            $menu = ApplicationMenu::onlyTrashed()->find($request->id);
            if (!$menu) return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);

            $menu->restore();

            return response()->json(['status' => 'success', 'action' => 'restore', 'data' => $menu]);
        }
    }
}
