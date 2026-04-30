<?php

namespace App\Services;

use App\Models\ApplicationMenu;

class MenuService
{
    public static function forUser($user): array
    {
        return ApplicationMenu::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('order')
            ->with([
                'permissions',
                'children.permissions',
                'children' => fn ($q) => $q->where('is_active', true)->orderBy('order'),
            ])
            ->get()
            ->flatMap(fn ($menu) => self::mapMenu($menu, $user))
            ->values()
            ->all();
    }

    protected static function mapMenu($menu, $user): array
    {
        // =========================
        // PERMISSION CHECK
        // =========================
        if ($menu->key !== null && ! self::userHasMenuPermission($menu, $user)) {
            return [];
        }

        // =========================
        // MAP CHILDREN
        // =========================
        $children = $menu->children
            ->flatMap(fn ($child) => self::mapMenu($child, $user))
            ->all();

        // =========================
        // HEADER
        // =========================
        if ($menu->key === null) {
            return empty($children)
                ? []
                : array_merge(
                    [[
                        'key' => null,
                        'title' => $menu->title,
                    ]],
                    $children
                );
        }

        // =========================
        // MENU ITEM
        // =========================
        $item = [
            'key' => $menu->key,
            'icon' => $menu->icon,
            'title' => $menu->title,
            'url' => $menu->url,
        ];

        if (! empty($children)) {
            $item['submenu'] = $children;
        }

        return [$item];
    }

    protected static function userHasMenuPermission($menu, $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('superadmin')) {
            return true;
        }

        // jika menu punya permission, cek intersect
        if ($menu->permissions->isNotEmpty()) {
            $userPermissions = $user->getAllPermissions()->pluck('name');

            return $menu->permissions
                ->pluck('name')
                ->intersect($userPermissions)
                ->isNotEmpty();
        }

        // jika menu header (key = null) atau menu tanpa permission, default false
        return false;
    }
}
