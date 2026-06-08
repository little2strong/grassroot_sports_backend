<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    

    public function index()
    {
        // if (is_null($this->user) || !$this->user->can('admin.user.view')) {
        //         abort(403, 'Sorry !! You are Unauthorized.');
        // }
        $data['title'] = 'Admin Users';
        $data['roles'] = Role::where('guard_name', 'admin')->with('permissions')->orderBy('name')->get();
        $data['permissions'] = Permission::where('guard_name', 'admin')
            ->orderBy('group_name')
            ->orderBy('name')
            ->get()
            ->groupBy('group_name');
        $data['admins'] = Admin::with('roles', 'permissions')->orderBy('name')->get();

        return view('admin.admins.index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'role' => 'required|string',
            'permissions' => 'nullable|array',
            'password' => 'nullable|string|min:6',
        ]);

        DB::beginTransaction();

        try {
            // Create or get role
            $role = Role::firstOrCreate(['name' => $request->role, 'guard_name' => 'admin']);

            // Sync permissions only if role is new
            if (!empty($request->permissions)) {
                $role->syncPermissions($request->permissions);
            }

            // Create admin user
            $user = new Admin();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->email_verified_at = now();
            $user->status = 1;
            $user->password = $request->password ? bcrypt($request->password) : bcrypt(str()->random(8));
            $user->save();

            // Assign role
            $user->assignRole($role);

            DB::commit();

            $permissionsCount = $user->getAllPermissions()->count();

            return response()->json([
                'message' => 'Admin user created successfully!',
                'userId' => $user->id,
                'userName' => $user->name,
                'userEmail' => $user->email,
                'userInitials' => strtoupper(substr($user->name, 0, 2)),
                'userRole' => $role->name,
                'permissionsCount' => $permissionsCount,
                'createdAt' => $user->created_at->format('d/m/Y'),
                'lastLogin' => $user->last_login_at?->format('d/m/Y') ?? '-',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin User Create Error: ' . $e->getMessage());

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function edit(Admin $user)
    {
        $role = $user->roles->first();
        $rolePermissions = $role ? $role->permissions->pluck('name')->toArray() : [];
        $userPermissions = $user->permissions->pluck('name')->toArray();
        // dd($rolePermissions, $userPermissions);
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'role' => $role?->name ?? '',
            'status' => $user->status,
            'rolePermissions' => $rolePermissions,
            'userPermissions' => $userPermissions
        ]);
    }


    public function update(Request $request, Admin $user)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'role'   => 'required|string',
            'status' => 'required|boolean',
        ]);

        DB::beginTransaction();

        try {
            // Update basic fields
            $user->update([
                'name'   => $request->name,
                'status' => $request->status,
            ]);

            // Get existing role (DO NOT create here)
            $role = Role::where('name', $request->role)
                ->where('guard_name', 'admin')
                ->firstOrFail();

            // Sync role to user (replace old role)
            $user->syncRoles([$role->name]);

            DB::commit();

            return response()->json([
                'message'           => 'Admin user updated successfully!',
                'userId'            => $user->id,
                'userName'          => $user->name,
                'userRole'          => $role->name,
                'permissionsCount'  => $user->getAllPermissions()->count(),
                'status'            => $user->status,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin User Update Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Something went wrong. Please try again.'
            ], 500);
        }
    }

    public function suspend(Admin $user)
    {
        try {
            $user->status = $user->status ? 0 : 1;
            $user->save();

            $message = $user->status ? 'User activated successfully!' : 'User suspended successfully!';

            return response()->json(['message' => $message]);
        } catch (\Exception $e) {
            Log::error('Admin User Suspend Error: ' . $e->getMessage());
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function changePassword(Request $request, $id)
    {
        try {
            $request->validate([
                'password' => 'required|min:8|confirmed',
            ]);

            $user = Admin::findOrFail($id);

            $user->update([
                'password' => bcrypt($request->password),
            ]);

            return back()->with('success', 'Password updated successfully');
        } catch (\Throwable $e) {
            // unexpected error
            Log::error('Password change failed', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }
}
