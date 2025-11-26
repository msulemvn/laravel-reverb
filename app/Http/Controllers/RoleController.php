<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Resources\RoleResource;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response as symfonyResponse;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::with('permissions:id,name')->paginate();
        $allPermissions = Permission::select('id', 'name')->get();

        $roles->getCollection()->transform(function ($role) use ($allPermissions) {
            $rolePermissionIds = $role->permissions->pluck('id')->toArray();

            $permissionsWithSelected = $allPermissions->map(function ($permission) use ($rolePermissionIds) {
                $permissionCopy = clone $permission;
                $permissionCopy->selected = in_array($permission->id, $rolePermissionIds);
                return $permissionCopy;
            });

            $categorizedPermissions = [];
            foreach ($permissionsWithSelected as $permission) {
                list($action, $model) = explode(':', $permission->name);

                if (!isset($categorizedPermissions[$model])) {
                    $categorizedPermissions[$model] = [];
                }

                $categorizedPermissions[$model][] = [
                    'id' => $permission->id,
                    'name' => $action,
                    'selected' => $permission->selected
                ];
            }

            $role->categorizedPermissions = $categorizedPermissions;

            return $role;
        });

        return Inertia::render('Roles', [
            'roles' => RoleResource::collection($roles)
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $permissions = $request->permissions;
        $role->name = $request->name;
        $role->syncPermissions($permissions);
        $role->save();

        logActivity(request: $request, description: "User updated roles", showable: false);
        return apiResponse(message: "Roles and permissions updated successfully");
    }

    public function store(Request $request)
    {
        $role = Role::create(['name' => $request->name]);
        logActivity(request: $request, description: "User created a new role", showable: true);
        return apiResponse(message: "Role created successfully", statusCode: symfonyResponse::HTTP_CREATED);
    }

    public function destroy(Request $request, Role $role)
    {

        logActivity(request: $request, description: "User deleted a post", showable: true);
        return apiResponse(message: "Post deleted successfully", statusCode: symfonyResponse::HTTP_NO_CONTENT);
    }
}
