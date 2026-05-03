<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================
        // 1. إنشاء الأدوار الستة
        // ============================================
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'super_admin',      'display_name' => 'مدير النظام', 'description' => 'يتحكم بكل شي في النظام',         'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'theater_manager',    'display_name' => 'مدير المسرح', 'description' => 'ينشئ ويعدل الفعاليات',            'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'event_manager',  'display_name' => 'مسؤول الفعاليات (الإعلام)', 'description' => 'ينشر الفعاليات ويحجز مقاعد الوفود', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'receptionist',     'display_name' => 'موظف الاستقبال',           'description' => 'يمسح QR ويسجل الحضور',            'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'university_office', 'display_name' => 'مدير مكتب رئيس الجامعة',  'description' => 'يشوف الإحصائيات فقط',             'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'user',             'display_name' => 'مستخدم عادي',              'description' => 'يحجز مقعد ويشوف تذكرته',          'created_at' => now(), 'updated_at' => now()],
        ]);


        // ============================================
        // 2. إنشاء حالات الفعاليات (8 حالات)
        // ============================================
        DB::table('statuses')->insert([
            ['id' => 1, 'name' => 'draft',        'display_name' => 'مسودة',        'description' => ' ما جهزت',               'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'added',        'display_name' => 'مضافة',        'description' => 'تم إضافتها من مدير المسرح',  'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'under_review', 'display_name' => 'قيد المراجعة', 'description' => 'مدير الاعلام يراجعها',        'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'active',       'display_name' => 'نشطة',         'description' => 'جاهزة للنشر',               'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'published',    'display_name' => 'منشورة',       'description' => 'الجمهور يشوفها ويحجز',      'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'closed',       'display_name' => 'مغلقة',        'description' => 'ما يقدر أحد يحجز',          'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'cancelled',    'display_name' => 'ملغاة',        'description' => 'تم إلغاء الفعالية',          'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'end',          'display_name' => 'منتهية',       'description' => 'فات وقت الفعالية',           'created_at' => now(), 'updated_at' => now()],
        ]);


        // ============================================
        // 3. إنشاء حساب مدير النظام الافتراضي
        // ============================================
        DB::table('users')->insert([
            'name'       => 'مدير النظام',
            'email'      => 'reem@uomosul.edu.iq',
            'password'   => Hash::make('123456'),
            'role_id'    => 1,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        // ============================================
        // 4. إنشاء أقسام المسرح الستة
        // ============================================

        DB::table('sections')->insert([
            ['id' => 1, 'name' => 'A', 'is_vip' => false, 'total_seats' => 202, 'total_rows' => 13, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'B', 'is_vip' => false, 'total_seats' => 189, 'total_rows' => 14, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'C', 'is_vip' => false, 'total_seats' => 193, 'total_rows' => 14, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'D', 'is_vip' => true,  'total_seats' => 117, 'total_rows' => 9,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'E', 'is_vip' => true,  'total_seats' => 179, 'total_rows' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'F', 'is_vip' => true,  'total_seats' => 117, 'total_rows' => 9,  'created_at' => now(), 'updated_at' => now()],
        ]);


        // ============================================
        // 5. إنشاء المقاعد
        // ============================================
        // ملاحظة: الصف 10 في الأقسام A, B, C مُعلّم كمقاعد وفود (is_vip_reserved = true)
        // المجموع: 21 + 15 + 16 = 52 مقعد وفود بالضبط
        // ============================================

        $seatMap = [
            // القسم A: 202 مقعد — 13 صف (صف 10 = وفود)
            1 => [1=>8, 2=>9, 3=>10, 4=>11, 5=>13, 6=>14, 7=>15, 8=>17, 9=>18, 10=>21, 11=>21, 12=>22, 13=>23],

            // القسم B: 189 مقعد — 14 صف (صف 10 = وفود)
            2 => [1=>10, 2=>11, 3=>11, 4=>12, 5=>12, 6=>13, 7=>13, 8=>14, 9=>14, 10=>15, 11=>15, 12=>16, 13=>16, 14=>17],

            // القسم C: 193 مقعد — 14 صف (صف 10 = وفود)
            3 => [1=>10, 2=>10, 3=>11, 4=>12, 5=>12, 6=>13, 7=>13, 8=>14, 9=>15, 10=>16, 11=>16, 12=>16, 13=>17, 14=>18],

            // القسم D (VIP): 117 مقعد — 9 صفوف
            4 => [1=>13, 2=>13, 3=>13, 4=>13, 5=>13, 6=>13, 7=>13, 8=>13, 9=>13],

            // القسم E (VIP): 179 مقعد — 10 صفوف
            5 => [1=>20, 2=>19, 3=>19, 4=>19, 5=>18, 6=>18, 7=>17, 8=>17, 9=>16, 10=>16],

            // القسم F (VIP): 117 مقعد — 9 صفوف
            6 => [1=>13, 2=>13, 3=>13, 4=>13, 5=>13, 6=>13, 7=>13, 8=>13, 9=>13],
        ];

        $sectionNames = [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F'];
        $totalSeats = 0;
        $totalVipSeats = 0;

        // ✨ الأقسام العادية (A, B, C) - الصف 10 فيها = مقاعد وفود
        $vipRowNumber = 10;
        $regularSectionsWithVip = [1, 2, 3]; // A, B, C

        foreach ($seatMap as $sectionId => $rows) {
            $sectionName = $sectionNames[$sectionId];
            $seats = [];

            foreach ($rows as $rowNumber => $seatCount) {
                // ✨ تحديد إذا كان هذا المقعد من مقاعد الوفود
                $isVipReserved = (
                    in_array($sectionId, $regularSectionsWithVip)
                    && $rowNumber === $vipRowNumber
                );

                for ($seatNum = 1; $seatNum <= $seatCount; $seatNum++) {
                    $seats[] = [
                        'section_id'      => $sectionId,
                        'row_number'      => $rowNumber,
                        'seat_number'     => $seatNum,
                        'label'           => $sectionName . '-' . str_pad($rowNumber, 2, '0', STR_PAD_LEFT) . '-' . str_pad($seatNum, 2, '0', STR_PAD_LEFT),
                        'is_vip_reserved' => $isVipReserved,  // ✨ جديد
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                    $totalSeats++;
                    if ($isVipReserved) $totalVipSeats++;
                }
            }

            DB::table('seats')->insert($seats);
        }

        // ✨ طباعة إحصائيات للتأكيد
        $this->command->info("✅ تم إنشاء {$totalSeats} مقعد إجمالاً");
        $this->command->info("✨ منها {$totalVipSeats} مقعد وفود (VIP)");
    }
}
