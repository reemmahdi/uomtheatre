<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DashboardWebController extends Controller
{
    // ============================================
    // الصفحة الرئيسية — كل الأدوار يشوفونها
    // ============================================
    public function index()
    {
        $roleName = session('role_name');

        // إحصائيات مشتركة
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();
        $totalRoles = Role::count();

        // توزيع الأدوار
        $roles = Role::withCount('users')->get();
        $roleColors = [
            'super_admin'      => '#e74c3c',
            'event_manager'    => '#f39c12',
            'theater_manager'  => '#2e75b6',
            'receptionist'     => '#27ae60',
            'university_office'=> '#8e44ad',
            'user'             => '#95a5a6',
        ];
        $rolesDistribution = $roles->map(function ($role) use ($roleColors) {
            return [
                'name'         => $role->name,
                'display_name' => $role->display_name,
                'count'        => $role->users_count,
                'color'        => $roleColors[$role->name] ?? '#95a5a6',
            ];
        });

        $recentUsers = User::with('role')->orderBy('created_at', 'desc')->take(5)->get();

        return view('dashboard.index', compact(
            'totalUsers', 'activeUsers', 'inactiveUsers', 'totalRoles',
            'rolesDistribution', 'recentUsers', 'roleName'
        ));
    }

    // ============================================
    // إدارة المستخدمين — فقط مدير النظام
    // ============================================
    public function users()
    {
        // حماية: فقط super_admin
        if (session('role_name') !== 'super_admin') {
            return redirect()->route('dashboard.index')
                ->with('error', 'ليس لديك صلاحية للوصول لهذه الصفحة');
        }

        $users = User::with('role')->orderBy('created_at', 'desc')->get();
        $roles = Role::all();
        return view('dashboard.users', compact('users', 'roles'));
    }

    public function storeUser(Request $request)
    {
        if (session('role_name') !== 'super_admin') {
            return redirect()->route('dashboard.index')->with('error', 'ليس لديك صلاحية');
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone'    => 'nullable|string',
            'role_id'  => 'required|exists:roles,id',
        ], [
            'name.required'     => 'الاسم مطلوب',
            'email.required'    => 'البريد مطلوب',
            'email.unique'      => 'البريد مستخدم من قبل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min'      => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'role_id.required'  => 'يجب اختيار الدور',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'phone'    => $request->phone,
            'role_id'  => $request->role_id,
        ]);

        return redirect()->route('dashboard.users')
            ->with('success', 'تم إنشاء المستخدم "' . $request->name . '" بنجاح');
    }

    public function updateUser(Request $request, $id)
    {
        if (session('role_name') !== 'super_admin') {
            return redirect()->route('dashboard.index')->with('error', 'ليس لديك صلاحية');
        }

        $user = User::findOrFail($id);

        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:users,email,' . $id,
            'phone'   => 'nullable|string',
            'role_id' => 'required|exists:roles,id',
        ]);

        $data = [
            'name'    => $request->name,
            'email'   => $request->email,
            'phone'   => $request->phone,
            'role_id' => $request->role_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        return redirect()->route('dashboard.users')
            ->with('success', 'تم تعديل بيانات "' . $user->name . '" بنجاح');
    }

    public function toggleStatus($id)
    {
        if (session('role_name') !== 'super_admin') {
            return redirect()->route('dashboard.index')->with('error', 'ليس لديك صلاحية');
        }

        $user = User::findOrFail($id);

        if ($user->id == session('user_id')) {
            return redirect()->route('dashboard.users')
                ->with('error', 'لا يمكنك تعطيل حسابك الشخصي');
        }

        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'تفعيل' : 'تعطيل';

        return redirect()->route('dashboard.users')
            ->with('success', 'تم ' . $status . ' حساب "' . $user->name . '"');
    }

    // ============================================
    // صفحة الفعاليات — مدير النظام + مسؤول الفعاليات + مدير المسرح
    // (Sprint 2 — حالياً صفحة مؤقتة)
    // ============================================
    public function events()
    {
        $allowed = ['super_admin', 'event_manager', 'theater_manager'];
        if (!in_array(session('role_name'), $allowed)) {
            return redirect()->route('dashboard.index')
                ->with('error', 'ليس لديك صلاحية للوصول لهذه الصفحة');
        }

        $events = Event::with(['status', 'creator'])->orderBy('event_date', 'desc')->get();
        return view('dashboard.events', compact('events'));
    }

    // ============================================
    // صفحة تسجيل الحضور — مدير النظام + موظف الاستقبال
    // (Sprint 3 — حالياً صفحة مؤقتة)
    // ============================================
    public function checkIn()
    {
        $allowed = ['super_admin', 'receptionist'];
        if (!in_array(session('role_name'), $allowed)) {
            return redirect()->route('dashboard.index')
                ->with('error', 'ليس لديك صلاحية للوصول لهذه الصفحة');
        }

        return view('dashboard.checkin');
    }

    // ============================================
    // صفحة الإحصائيات — مدير النظام + مدير المسرح + مكتب الرئيس
    // (Sprint 3 — حالياً صفحة مؤقتة)
    // ============================================
    public function stats()
    {
        $allowed = ['super_admin', 'theater_manager', 'university_office'];
        if (!in_array(session('role_name'), $allowed)) {
            return redirect()->route('dashboard.index')
                ->with('error', 'ليس لديك صلاحية للوصول لهذه الصفحة');
        }

        return view('dashboard.stats');
    }
}
