<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ════════════════════════════════════════════════════════════
 * PermissionsSeeder — UOMTheatre (مُحدّث - إصلاحات Claude)
 * ════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات:
 *   🔴 idempotent (updateOrInsert بدل insert) - تشغيل مرتين آمن
 *   🟡 DB::transaction (atomic - لو فشل insert، rollback)
 *   🟡 إعادة بناء role_permission (delete + insert) لتجنب duplicates
 *
 * يعبّئ الصلاحيات الافتراضية + يربطها بالأدوار حسب الـ workflow الجديد.
 *
 * ════════════════════════════════════════════════════════════
 */
class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedPermissions();
            $this->seedRolePermissions();
        });

        $this->command->info('✅ Seeding صلاحيات اكتمل بنجاح');
    }

    /**
     * ✨ مُصحَّح: updateOrInsert بدل insert (idempotent)
     */
    protected function seedPermissions(): void
    {
        $permissions = [
            // مجموعة: إدارة الفعاليات
            ['name' => 'events.create',           'display_name' => 'إنشاء فعالية',           'group' => 'events',     'description' => 'إنشاء فعالية جديدة كمسودة'],
            ['name' => 'events.edit',             'display_name' => 'تعديل فعالية',           'group' => 'events',     'description' => 'تعديل بيانات الفعالية'],
            ['name' => 'events.delete',           'display_name' => 'حذف فعالية',             'group' => 'events',     'description' => 'حذف فعالية (مسودة فقط)'],
            ['name' => 'events.send_for_approval','display_name' => 'إرسال للموافقة',         'group' => 'events',     'description' => 'إرسال الفعالية للجهات المختصة'],
            ['name' => 'events.cancel',           'display_name' => 'إلغاء فعالية',           'group' => 'events',     'description' => 'إلغاء فعالية وإرسال إشعارات'],

            // مجموعة: الموافقات
            ['name' => 'events.approve_theater',  'display_name' => 'موافقة مدير المسرح',     'group' => 'approvals',  'description' => 'موافقة الفعاليات من جانب مدير المسرح'],
            ['name' => 'events.approve_office',   'display_name' => 'موافقة مكتب الرئيس',     'group' => 'approvals',  'description' => 'موافقة الفعاليات من جانب مكتب رئيس الجامعة'],

            // مجموعة: النشر
            ['name' => 'events.publish',          'display_name' => 'نشر للجمهور',            'group' => 'publishing', 'description' => 'نشر الفعالية للجمهور بعد اكتمال الموافقات'],
            ['name' => 'events.notify_all',       'display_name' => 'إرسال إشعار للجميع',     'group' => 'publishing', 'description' => 'إرسال إشعارات للمستخدمين'],

            // مجموعة: الوفود والمقاعد
            ['name' => 'vip.manage',              'display_name' => 'إدارة حجز الوفود',        'group' => 'vip',        'description' => 'حجز وإدارة مقاعد الوفود'],
            ['name' => 'vip.assign_seats',        'display_name' => 'تحديد مقاعد الجمهور',    'group' => 'vip',        'description' => 'تحديد المقاعد المتاحة للحجز عبر التطبيق'],

            // مجموعة: الحضور
            ['name' => 'checkin.scan',            'display_name' => 'مسح QR وتسجيل الحضور',   'group' => 'checkin',    'description' => 'مسح رموز QR للتذاكر'],

            // مجموعة: الإدارة
            ['name' => 'users.manage',            'display_name' => 'إدارة المستخدمين',        'group' => 'admin',      'description' => 'إضافة وتعديل وحذف المستخدمين'],
            ['name' => 'stats.view',              'display_name' => 'عرض الإحصائيات',          'group' => 'admin',      'description' => 'الوصول إلى لوحة الإحصائيات'],
            ['name' => 'permissions.manage',      'display_name' => 'إدارة الصلاحيات',         'group' => 'admin',      'description' => 'تخصيص صلاحيات الأدوار من شاشة الصلاحيات'],
        ];

        $now = now();
        foreach ($permissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],   // ← الشرط (للبحث)
                [                            // ← القيم
                    'display_name' => $perm['display_name'],
                    'description'  => $perm['description'],
                    'group'        => $perm['group'],
                    'updated_at'   => $now,
                    'created_at'   => $now,
                ]
            );
        }

        $this->command->info('✅ ' . count($permissions) . ' صلاحية تم إعدادها (insert/update)');
    }

    /**
     * ✨ مُصحَّح: حذف وإعادة insert role_permission (idempotent)
     */
    protected function seedRolePermissions(): void
    {
        $roles = DB::table('roles')->pluck('id', 'name');
        $perms = DB::table('permissions')->pluck('id', 'name');

        $rolePermissions = [
            // ────────────────────────────────────────
            // 🟣 مدير النظام (super_admin) — كل الصلاحيات
            // ────────────────────────────────────────
            'super_admin' => [
                'events.create', 'events.edit', 'events.delete',
                'events.send_for_approval', 'events.cancel',
                'events.approve_theater', 'events.approve_office',
                'events.publish', 'events.notify_all',
                'vip.manage', 'vip.assign_seats',
                'checkin.scan',
                'users.manage', 'stats.view', 'permissions.manage',
            ],

            // ────────────────────────────────────────
            // 🔵 مدير الإعلام (event_manager) — ينشئ + ينشر + يدير الوفود
            // ────────────────────────────────────────
            'event_manager' => [
                'events.create', 'events.edit', 'events.delete',
                'events.send_for_approval',
                'events.cancel',
                'events.publish', 'events.notify_all',
                'vip.manage', 'vip.assign_seats',
            ],

            // ────────────────────────────────────────
            // 🟡 مدير المسرح (theater_manager) — يوافق فقط
            // ────────────────────────────────────────
            'theater_manager' => [
                'events.approve_theater',
            ],

            // ────────────────────────────────────────
            // 🟢 مكتب رئيس الجامعة (university_office) — يوافق + يشاهد الإحصائيات
            // ────────────────────────────────────────
            'university_office' => [
                'events.approve_office',
                'stats.view',
            ],

            // ────────────────────────────────────────
            // 🔴 موظف الاستقبال (receptionist) — مسح QR فقط
            // ────────────────────────────────────────
            'receptionist' => [
                'checkin.scan',
            ],

            // ────────────────────────────────────────
            // ⚪ مستخدم عادي (user) — لا صلاحيات إدارية
            // ────────────────────────────────────────
            'user' => [],
        ];

        // ✨ مُصحَّح: حذف الموجود لكل دور قبل insert (تجنب duplicates)
        $now = now();
        $totalLinks = 0;

        foreach ($rolePermissions as $roleName => $permNames) {
            $roleId = $roles[$roleName] ?? null;
            if (!$roleId) {
                $this->command->warn("⚠️ الدور غير موجود: {$roleName}");
                continue;
            }

            // حذف الصلاحيات الحالية لهذا الدور
            DB::table('role_permission')->where('role_id', $roleId)->delete();

            // إعادة إضافتها
            foreach ($permNames as $permName) {
                $permId = $perms[$permName] ?? null;
                if (!$permId) {
                    $this->command->warn("⚠️ الصلاحية غير موجودة: {$permName}");
                    continue;
                }

                DB::table('role_permission')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => $permId,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
                $totalLinks++;
            }
        }

        $this->command->info("✅ {$totalLinks} ربط أدوار-صلاحيات تم إعداده");
    }
}
