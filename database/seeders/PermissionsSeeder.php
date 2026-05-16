<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ════════════════════════════════════════════════════════════
 * PermissionsSeeder — UOMTheatre (تصميم جديد)
 * ════════════════════════════════════════════════════════════
 *
 * 🎯 التعديلات:
 *   ❌ حُذف:    events.approve_theater (مدير المسرح ما يوافق)
 *   ✅ بقاء:    events.approve_office  (مكتب الرئاسة فقط)
 *
 * 🟣 idempotent (updateOrInsert) - تشغيل مرتين آمن
 * 🟡 DB::transaction - atomic
 * 🟡 إعادة بناء role_permission لتجنب duplicates
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
            $this->cleanupObsoletePermissions();  // ✨ جديد - تنظيف الصلاحيات المهجورة
        });

        $this->command->info('✅ Seeding صلاحيات اكتمل بنجاح');
    }

    /**
     * تسجيل/تحديث الصلاحيات (idempotent)
     */
    protected function seedPermissions(): void
    {
        $permissions = [
            // مجموعة: إدارة الفعاليات
            ['name' => 'events.create',           'display_name' => 'إنشاء فعالية',           'group' => 'events',     'description' => 'إنشاء فعالية جديدة كمسودة'],
            ['name' => 'events.edit',             'display_name' => 'تعديل فعالية',           'group' => 'events',     'description' => 'تعديل بيانات الفعالية'],
            ['name' => 'events.delete',           'display_name' => 'حذف فعالية',             'group' => 'events',     'description' => 'حذف فعالية (مسودة فقط)'],
            ['name' => 'events.send_for_approval','display_name' => 'إرسال للموافقة',         'group' => 'events',     'description' => 'إرسال الفعالية لمكتب الرئاسة'],
            ['name' => 'events.cancel',           'display_name' => 'إلغاء فعالية',           'group' => 'events',     'description' => 'إلغاء فعالية وإرسال إشعارات'],
            ['name' => 'events.view',             'display_name' => 'عرض الفعاليات',          'group' => 'events',     'description' => 'مشاهدة قائمة الفعاليات والتفاصيل'],

            // مجموعة: الموافقات (✨ مُحدَّث - بدون approve_theater)
            ['name' => 'events.approve_office',   'display_name' => 'موافقة مكتب الرئاسة',    'group' => 'approvals',  'description' => 'موافقة أو رفض الفعاليات من جانب مكتب رئاسة الجامعة'],

            // مجموعة: النشر
            ['name' => 'events.publish',          'display_name' => 'نشر للجمهور',            'group' => 'publishing', 'description' => 'نشر الفعالية للجمهور بعد موافقة الرئاسة'],
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
                ['name' => $perm['name']],
                [
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
     * ربط الصلاحيات بالأدوار (✨ مُحدَّث للتصميم الجديد)
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
                'events.send_for_approval', 'events.cancel', 'events.view',
                'events.approve_office',  // ✨ بدل approve_theater + approve_office
                'events.publish', 'events.notify_all',
                'vip.manage', 'vip.assign_seats',
                'checkin.scan',
                'users.manage', 'stats.view', 'permissions.manage',
            ],

            // ────────────────────────────────────────
            // 🔵 مدير الإعلام (event_manager)
            //    ينشئ + يرسل للموافقة + ينشر + يدير الوفود
            // ────────────────────────────────────────
            'event_manager' => [
                'events.create', 'events.edit', 'events.delete',
                'events.send_for_approval',
                'events.cancel', 'events.view',
                'events.publish',         // ✨ النشر بيد مدير الإعلام
                'events.notify_all',
                'vip.manage', 'vip.assign_seats',
            ],

            // ────────────────────────────────────────
            // 🟡 مدير المسرح (theater_manager) — ✨ مُحدَّث
            //    صار "مشاهد فقط" - بدون قرار
            // ────────────────────────────────────────
            'theater_manager' => [
                'events.view',  // ✨ فقط مشاهدة الفعاليات (للمتابعة)
            ],

            // ────────────────────────────────────────
            // 🟢 مكتب رئيس الجامعة (university_office)
            //    يوافق/يرفض + يشاهد الإحصائيات
            // ────────────────────────────────────────
            'university_office' => [
                'events.approve_office',
                'events.view',
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

        $now = now();
        $totalLinks = 0;

        foreach ($rolePermissions as $roleName => $permNames) {
            $roleId = $roles[$roleName] ?? null;
            if (!$roleId) {
                $this->command->warn("⚠️ الدور غير موجود: {$roleName}");
                continue;
            }

            // حذف الصلاحيات الحالية لهذا الدور (تنظيف)
            DB::table('role_permission')->where('role_id', $roleId)->delete();

            // إعادة إضافة الصلاحيات الجديدة
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

    /**
     * ✨ جديد: حذف الصلاحيات المهجورة من النظام
     * (تجنّب ترك سجلات قديمة لا تستخدم)
     */
    protected function cleanupObsoletePermissions(): void
    {
        $obsoletePermissions = [
            'events.approve_theater',  // مدير المسرح ما عاد يوافق
        ];

        $deletedCount = 0;
        foreach ($obsoletePermissions as $permName) {
            $permId = DB::table('permissions')->where('name', $permName)->value('id');
            if ($permId) {
                // حذف من role_permission أولاً (FK)
                DB::table('role_permission')->where('permission_id', $permId)->delete();
                // حذف الصلاحية نفسها
                DB::table('permissions')->where('id', $permId)->delete();
                $deletedCount++;
                $this->command->info("  🗑️  حُذفت الصلاحية المهجورة: {$permName}");
            }
        }

        if ($deletedCount === 0) {
            $this->command->info('  ℹ️  لا توجد صلاحيات مهجورة');
        }
    }
}
