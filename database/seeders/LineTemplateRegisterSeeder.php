<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Gametech\LineOA\Models\LineTemplate;

class LineTemplateRegisterSeeder extends Seeder
{
    public function run()
    {
        $templates = [

            // ----------------------------------------------------
            // WELCOME: ข้อความต้อนรับลูกค้า (JSON Template)
            // ----------------------------------------------------
            [
                'category'     => 'welcome',
                'key'          => 'welcome.default',
                'message_type' => 'json',
                'message'      => json_encode([
                    'version'  => 1,
                    'messages' => [
                        [
                            'kind'    => 'text',
                            'text'    => 'สวัสดีค่ะ {{display_name}} ?' . "\n"
                                . 'ตอนนี้มีโปรสำหรับลูกค้าใหม่อยู่ สนใจถามรายละเอียดเพิ่มเติมได้เลยนะคะ',
                            'options' => [
                                'placeholders' => [
                                    'display_name' => 'ชื่อที่โชว์ใน LINE',
                                ],
                            ],
                        ],
                        [
                            'kind'     => 'image',
                            'original' => 'https://thegrand789.com/storage/slide_img/Xc4H0Xx1v8.jpg',
                            'preview'  => 'https://thegrand789.com/storage/slide_img/Xc4H0Xx1v8.jpg',
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                'description'  => 'ข้อความ ต้อนรับลูกค้า',
            ],

            // ----------------------------------------------------
            // STEP 1: ขอเบอร์โทร
            // ----------------------------------------------------
            [
                'category'    => 'register',
                'key'         => 'register.ask_phone',
                'message'     => "กรุณาพิมพ์เบอร์โทร 10 หลัก ที่ต้องการใช้สมัครสมาชิกค่ะ",
                'description' => "ข้อความถามเบอร์โทรเริ่มต้น",
            ],
            [
                'category'    => 'register',
                'key'         => 'register.error_phone_invalid',
                'message'     => "เบอร์ {input} ไม่ถูกต้องค่ะ กรุณากรอกเป็นเลข 10 หลัก เช่น 0891234567",
                'description' => "ข้อความเมื่อเบอร์โทรรูปแบบไม่ถูกต้อง",
            ],
            [
                'category'    => 'register',
                'key'         => 'register.error_phone_used',
                'message'     => "เบอร์ {phone} ถูกใช้สมัครแล้วค่ะ กรุณาใช้เบอร์อื่น หรือแจ้งเจ้าหน้าที่",
                'description' => "ข้อความเมื่อเบอร์ซ้ำกับสมาชิกอื่น",
            ],

            // ----------------------------------------------------
            // STEP 2: ชื่อจริง
            // ----------------------------------------------------
            [
                'category'    => 'register',
                'key'         => 'register.ask_name',
                'message'     => "กรุณาพิมพ์ชื่อจริงของคุณค่ะ",
                'description' => "ข้อความถามชื่อจริง",
            ],
            [
                'category'    => 'register',
                'key'         => 'register.error_name_invalid',
                'message'     => "ชื่อที่ส่งมาไม่ถูกต้องค่ะ กรุณาลองใหม่",
                'description' => "ข้อความเมื่อชื่อรูปแบบไม่ถูกต้อง",
            ],

            // ----------------------------------------------------
            // STEP 3: นามสกุล
            // ----------------------------------------------------
            [
                'category'    => 'register',
                'key'         => 'register.ask_surname',
                'message'     => "กรุณาพิมพ์นามสกุลของคุณค่ะ",
                'description' => "ข้อความถามนามสกุล",
            ],
            [
                'category'    => 'register',
                'key'         => 'register.error_surname_invalid',
                'message'     => "นามสกุลไม่ถูกต้องค่ะ กรุณาพิมพ์ใหม่อีกครั้ง",
                'description' => "ข้อความเมื่อรูปแบบนามสกุลไม่ถูกต้อง",
            ],

            // ----------------------------------------------------
            // STEP 4: ธนาคาร
            // ----------------------------------------------------
            [
                'category'    => 'register',
                'key'         => 'register.ask_bank',
                'message'     => "กรุณาระบุชื่อธนาคารของคุณ เช่น กสิกร ไทยพาณิชย์ กรุงไทย กรุงศรี เป็นต้น",
                'description' => "ข้อความถามธนาคาร",
            ],
            [
                'category'    => 'register',
                'key'         => 'register.error_bank_invalid',
                'message'     => "ไม่พบธนาคาร {input} ในระบบค่ะ กรุณาพิมพ์ชื่อธนาคารให้ถูกต้อง",
                'description' => "ข้อความเมื่อธนาคารไม่ถูกต้องหรือแมพไม่ได้",
            ],

            // ----------------------------------------------------
            // STEP 5: เลขบัญชี
            // ----------------------------------------------------
            [
                'category'    => 'register',
                'key'         => 'register.ask_account',
                'message'     => "กรุณาพิมพ์เลขที่บัญชีธนาคารของคุณค่ะ (ไม่ต้องใส่ขีด)",
                'description' => "ข้อความถามเลขบัญชี",
            ],
            [
                'category'    => 'register',
                'key'         => 'register.error_account_invalid',
                'message'     => "เลขที่บัญชี {input} ไม่ถูกต้องค่ะ ตรวจสอบใหม่อีกครั้งนะคะ",
                'description' => "ข้อความเมื่อเลขบัญชีรูปแบบไม่ถูกต้อง",
            ],
            [
                'category'    => 'register',
                'key'         => 'register.error_account_used',
                'message'     => "เลขบัญชี {account_no} ถูกใช้สมัครแล้วค่ะ กรุณาใช้บัญชีอื่น",
                'description' => "ข้อความเมื่อเลขบัญชีซ้ำ",
            ],

            // ----------------------------------------------------
            // FINAL: สมัครสำเร็จ
            // ----------------------------------------------------
            [
                'category'    => 'register',
                'key'         => 'register.complete_success',
                'message'     => "สมัครสมาชิกเรียบร้อยแล้วค่ะ 🎉\nยูส: {username}\nรหัสผ่าน: {password}\nลิงก์เข้าเล่น: {login_url}\n\nกรุณาเก็บข้อมูลไว้เป็นความลับนะคะ",
                'description' => "ข้อความเมื่อสมัครเสร็จสมบูรณ์",
            ],

            // ----------------------------------------------------
            // CANCEL / ALREADY
            // ----------------------------------------------------
            [
                'category'    => 'register',
                'key'         => 'register.cancelled',
                'message'     => "ยกเลิกการสมัครเรียบร้อยแล้วค่ะ หากต้องการเริ่มใหม่ พิมพ์คำว่า 'สมัคร' ได้เลยค่ะ",
                'description' => "ข้อความเมื่อผู้ใช้ยกเลิก",
            ],
            [
                'category'    => 'register',
                'key'         => 'register.already_completed',
                'message'     => "คุณสมัครสมาชิกเรียบร้อยแล้วค่ะ หากลืมยูสหรือต้องการความช่วยเหลือ แจ้งเจ้าหน้าที่ได้เลยนะคะ",
                'description' => "ข้อความเมื่อ session สำเร็จแล้วแต่ผู้ใช้ยังพิมพ์สมัคร",
            ],

            // ----------------------------------------------------
            // SYSTEM ERROR
            // ----------------------------------------------------
            [
                'category'    => 'register',
                'key'         => 'register.error_system',
                'message'     => "ไม่สามารถสมัครสมาชิกได้ในขณะนี้ค่ะ (เหตุผล: {reason}) กรุณาลองใหม่อีกครั้งหรือแจ้งเจ้าหน้าที่",
                'description' => "ข้อความ fallback หากสมัครล้มเหลวจากระบบ",
            ],
        ];

        foreach ($templates as $item) {
            LineTemplate::updateOrCreate(
                ['key' => $item['key']],
                [
                    'category'     => $item['category'],
                    'message_type' => $item['message_type'] ?? 'text',
                    'message'      => $item['message'],
                    'description'  => $item['description'],
                    'enabled'      => true,
                ]
            );
        }

    }
}
