<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ════════════════════════════════════════════════════════════════
 * UserController — UOMTheatre API (مُحدّث - إصلاحات Claude)
 * ════════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات:
 *   🔴 إزالة hardcoded role_id=6 → Role::USER ديناميكي
 *   🔴 منع إنشاء/ترقية إلى super_admin من API (security)
 *   🟡 منع تعطيل الحساب الشخصي
 *
 * ════════════════════════════════════════════════════════════════
 */
class UserController extends Controller
{
    /**
     * ✨ helper: التأكد أن الدور المختار ليس super_admin
     */
    protected function rejectSuperAdminRole(int $roleId): ?JsonResponse
    {
        $superAdminId = Role::where('name', Role::SUPER_ADMIN)->value('id');
        if ($roleId === $superAdminId) {
            return response()->json([
                'message' => 'لا يمكن إنشاء/تعيين دور super_admin من API',
            ], 403);
        }
        return null;
    }

    public function index(): JsonResponse
    {
        // ✨ مُصحَّح: ديناميكي بدل hardcoded 6
        $userRoleId = Role::where('name', Role::USER)->value('id');

        $users = User::with('role')
            ->where('role_id', '!=', $userRoleId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['users' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|min:8',   // ✨ زيادة الحد الأدنى
            'phone'    => 'nullable|string',
            'role_id'  => 'required|exists:roles,id',
        ]);

        // ✨ منع إنشاء super_admin من API
        if ($rejection = $this->rejectSuperAdminRole((int) $request->role_id)) {
            return $rejection;
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'phone'    => $request->phone,
            'role_id'  => $request->role_id,
        ]);

        return response()->json([
            'message' => 'تم إنشاء الموظف بنجاح',
            'user'    => $user->load('role'),
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $user = User::with('role')->findOrFail($id);
        return response()->json(['user' => $user]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // ✨ منع تعديل super_admin من API
        if ($user->isSuperAdmin()) {
            return response()->json([
                'message' => 'حسابات super_admin لا يمكن تعديلها من API',
            ], 403);
        }

        $request->validate([
            'name'    => 'sometimes|string|max:255',
            'email'   => 'sometimes|email|unique:users,email,' . $id,
            'phone'   => 'nullable|string',
            'role_id' => 'sometimes|exists:roles,id',
        ]);

        // ✨ منع ترقية إلى super_admin
        if ($request->has('role_id')) {
            if ($rejection = $this->rejectSuperAdminRole((int) $request->role_id)) {
                return $rejection;
            }
        }

        $user->update($request->only(['name', 'email', 'phone', 'role_id']));

        return response()->json([
            'message' => 'تم تعديل الموظف بنجاح',
            'user'    => $user->fresh(['role']),
        ]);
    }

    public function toggleStatus($id, Request $request): JsonResponse
    {
        $user = User::findOrFail($id);

        // ✨ منع تعطيل الحساب الشخصي
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'لا يمكنك تعطيل حسابك الشخصي',
            ], 422);
        }

        // ✨ منع تعطيل super_admin من API
        if ($user->isSuperAdmin()) {
            return response()->json([
                'message' => 'حسابات super_admin لا يمكن تعطيلها من API',
            ], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'تم تفعيل' : 'تم تعطيل';

        return response()->json([
            'message' => $status . ' حساب ' . $user->name,
            'user'    => $user,
        ]);
    }

    public function roles(): JsonResponse
    {
        // ✨ استبعاد super_admin (لا يجب أن يُختار من الـ form)
        $roles = Role::whereNotIn('name', [Role::SUPER_ADMIN])->get();
        return response()->json(['roles' => $roles]);
    }
}
