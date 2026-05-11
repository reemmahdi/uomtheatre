<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * ════════════════════════════════════════════════════════════
 * DatabaseSeeder — UOMTheatre (إعادة هندسة)
 * ════════════════════════════════════════════════════════════
 *
 * 🎯 التعديل المعماري:
 *   - is_vip_reserved = false لكل المقاعد (محايد)
 *     السبب: مقاعد الوفود تُحدد per-event من جدول reservations
 *   - is_vip للأقسام = false لكل الأقسام (legacy - لا تأثير عملي)
 *
 * ════════════════════════════════════════════════════════════
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedRoles();
            $this->seedStatuses();
            $this->seedSuperAdmin();
            $this->seedSections();
            $this->seedSeats();
        });

        $this->call(PermissionsSeeder::class);

        $this->command->info('🎉 Seeding اكتمل');
    }

    protected function seedRoles(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'super_admin',       'display_name' => 'مدير النظام',                'description' => 'يتحكم بكل شي في النظام'],
            ['id' => 2, 'name' => 'theater_manager',   'display_name' => 'مدير المسرح',                'description' => 'يوافق على الفعاليات'],
            ['id' => 3, 'name' => 'event_manager',     'display_name' => 'مسؤول الفعاليات (الإعلام)',  'description' => 'ينشئ الفعاليات وينشرها'],
            ['id' => 4, 'name' => 'receptionist',      'display_name' => 'موظف الاستقبال',             'description' => 'يمسح QR ويسجل الحضور'],
            ['id' => 5, 'name' => 'university_office', 'display_name' => 'مدير مكتب رئيس الجامعة',     'description' => 'يوافق ويشاهد الإحصائيات'],
            ['id' => 6, 'name' => 'user',              'display_name' => 'مستخدم عادي',                'description' => 'يحجز مقعد ويشاهد تذكرته'],
        ];

        $now = now();
        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $role['id']],
                [
                    'name'         => $role['name'],
                    'display_name' => $role['display_name'],
                    'description'  => $role['description'],
                    'updated_at'   => $now,
                    'created_at'   => $now,
                ]
            );
        }
        $this->command->info('✅ 6 أدوار');
    }

    protected function seedStatuses(): void
    {
        $statuses = [
            ['name' => 'draft',     'display_name' => 'مسودة',     'description' => 'ما جهزت بعد'],
            ['name' => 'added',     'display_name' => 'مضافة',     'description' => 'بانتظار الموافقات'],
            ['name' => 'active',    'display_name' => 'نشطة',      'description' => 'وافق عليها الجميع'],
            ['name' => 'published', 'display_name' => 'منشورة',    'description' => 'الجمهور يحجز'],
            ['name' => 'closed',    'display_name' => 'مغلقة',     'description' => 'الحجز مغلق'],
            ['name' => 'cancelled', 'display_name' => 'ملغاة',     'description' => 'تم الإلغاء'],
            ['name' => 'end',       'display_name' => 'منتهية',    'description' => 'انتهى الوقت'],
        ];

        $now = now();
        foreach ($statuses as $status) {
            DB::table('statuses')->updateOrInsert(
                ['name' => $status['name']],
                [
                    'display_name' => $status['display_name'],
                    'description'  => $status['description'],
                    'updated_at'   => $now,
                    'created_at'   => $now,
                ]
            );
        }
        $this->command->info('✅ 7 حالات');
    }

    protected function seedSuperAdmin(): void
    {
        $email = env('SEEDER_ADMIN_EMAIL', 'reem@uomosul.edu.iq');
        $password = env('SEEDER_ADMIN_PASSWORD', null);

        if (!$password) {
            $this->command->warn('⚠️ SEEDER_ADMIN_PASSWORD غير مضبوطة - استخدام كلمة مرور افتراضية');
            $password = '123456';
        }

        DB::table('users')->updateOrInsert(
            ['email' => $email],
            [
                'name'       => 'مدير النظام',
                'password'   => Hash::make($password),
                'role_id'    => 1,
                'is_active'  => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $this->command->info("✅ super_admin: {$email}");
    }

    /**
     * 🎯 الأقسام - is_vip = false لكل الأقسام
     * (legacy column - لا تأثير على الـ logic الجديد)
     */
    protected function seedSections(): void
    {
        $sections = [
            ['id' => 1, 'name' => 'A', 'total_seats' => 202, 'total_rows' => 13],
            ['id' => 2, 'name' => 'B', 'total_seats' => 189, 'total_rows' => 14],
            ['id' => 3, 'name' => 'C', 'total_seats' => 193, 'total_rows' => 14],
            ['id' => 4, 'name' => 'D', 'total_seats' => 117, 'total_rows' => 9],
            ['id' => 5, 'name' => 'E', 'total_seats' => 179, 'total_rows' => 10],
            ['id' => 6, 'name' => 'F', 'total_seats' => 117, 'total_rows' => 9],
        ];

        $now = now();
        foreach ($sections as $section) {
            DB::table('sections')->updateOrInsert(
                ['id' => $section['id']],
                [
                    'name'        => $section['name'],
                    'is_vip'      => false,   // 🎯 محايد - الـ VIP يحدد per-event
                    'total_seats' => $section['total_seats'],
                    'total_rows'  => $section['total_rows'],
                    'updated_at'  => $now,
                    'created_at'  => $now,
                ]
            );
        }
        $this->command->info('✅ 6 أقسام (is_vip محايد)');
    }

    /**
     * 🎯 المقاعد - is_vip_reserved = false لكل المقاعد
     * (محايد - الـ VIP يحدد per-event)
     */
    protected function seedSeats(): void
    {
        if (DB::table('seats')->count() > 0) {
            $this->command->info('ℹ️ المقاعد موجودة - تخطّي');
            return;
        }

        $seatMap = [
            1 => [1=>8, 2=>9, 3=>10, 4=>11, 5=>13, 6=>14, 7=>15, 8=>17, 9=>18, 10=>21, 11=>21, 12=>22, 13=>23],
            2 => [1=>10, 2=>11, 3=>11, 4=>12, 5=>12, 6=>13, 7=>13, 8=>14, 9=>14, 10=>15, 11=>15, 12=>16, 13=>16, 14=>17],
            3 => [1=>10, 2=>10, 3=>11, 4=>12, 5=>12, 6=>13, 7=>13, 8=>14, 9=>15, 10=>16, 11=>16, 12=>16, 13=>17, 14=>18],
            4 => [1=>13, 2=>13, 3=>13, 4=>13, 5=>13, 6=>13, 7=>13, 8=>13, 9=>13],
            5 => [1=>20, 2=>19, 3=>19, 4=>19, 5=>18, 6=>18, 7=>17, 8=>17, 9=>16, 10=>16],
            6 => [1=>13, 2=>13, 3=>13, 4=>13, 5=>13, 6=>13, 7=>13, 8=>13, 9=>13],
        ];

        $sectionNames = [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F'];
        $totalSeats = 0;

        $now = now();
        foreach ($seatMap as $sectionId => $rows) {
            $sectionName = $sectionNames[$sectionId];
            $seats = [];

            foreach ($rows as $rowNumber => $seatCount) {
                for ($seatNum = 1; $seatNum <= $seatCount; $seatNum++) {
                    $seats[] = [
                        'section_id'      => $sectionId,
                        'row_number'      => $rowNumber,
                        'seat_number'     => $seatNum,
                        'label'           => $sectionName . '-'
                                          . str_pad($rowNumber, 2, '0', STR_PAD_LEFT) . '-'
                                          . str_pad($seatNum, 2, '0', STR_PAD_LEFT),
                        'is_vip_reserved' => false,   // 🎯 كله false - VIP per-event
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                    $totalSeats++;
                }
            }

            DB::table('seats')->insert($seats);
        }

        $this->command->info("✅ {$totalSeats} مقعد (is_vip_reserved محايد)");
    }
}
