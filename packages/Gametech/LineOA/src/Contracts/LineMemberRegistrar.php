<?php

namespace Gametech\LineOA\Contracts;

/**
 * สัญญา (contract) สำหรับ class ที่ทำหน้าที่ "สร้างสมาชิกจากข้อมูลที่มาจาก LINE"
 *
 * RegisterFlowService จะเรียกผ่าน interface นี้เท่านั้น
 * เพื่อไม่ผูกกับโครงสร้าง members ของเว็บโดยตรง
 */
interface LineMemberRegistrar
{
    /**
     * สมัครสมาชิกจากข้อมูลที่เก็บมาจาก flow LINE
     *
     * ตัวอย่างข้อมูลใน $data:
     * [
     *   'phone'          => '0891234567',
     *   'name'           => 'สมชาย',
     *   'surname'        => 'ใจดี',
     *   'bank_code'      => 'KBANK',
     *   'account_no'     => '1234567890',
     *   'line_contact_id'=> 123,
     * ]
     *
     * คุณต้องผูก logic ตรงนี้เข้ากับระบบสมาชิกจริงของเว็บ
     */
    public function registerFromLineData(array $data): MemberRegistrationResult;
}
