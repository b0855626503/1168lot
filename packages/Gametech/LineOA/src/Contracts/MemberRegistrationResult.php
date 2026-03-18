<?php

namespace Gametech\LineOA\Contracts;

/**
 * ใช้เป็นผลลัพธ์มาตรฐานจากการสมัครสมาชิกผ่าน LINE
 */
class MemberRegistrationResult
{
    /** สมัครสำเร็จหรือไม่ */
    public bool $success = false;

    /** รหัสสมาชิก (primary key ของ member) */
    public ?int $memberId = null;

    /** username ที่ใช้เข้าเล่น */
    public ?string $username = null;

    /** password ตั้งต้น (ควรให้เปลี่ยนหลังล็อกอินครั้งแรก) */
    public ?string $password = null;

    /** ลิงก์หน้า login */
    public ?string $loginUrl = null;

    /** ข้อความอธิบาย (กรณี error หรืออยากส่งไป alert/log) */
    public ?string $message = null;

    public static function success(
        int $memberId,
        string $username,
        string $password,
        ?string $loginUrl = null,
        ?string $message = null
    ): self {
        $self = new self();
        $self->success   = true;
        $self->memberId  = $memberId;
        $self->username  = $username;
        $self->password  = $password;
        $self->loginUrl  = $loginUrl;
        $self->message   = $message;

        return $self;
    }

    public static function failure(?string $message = null): self
    {
        $self = new self();
        $self->success  = false;
        $self->message  = $message;

        return $self;
    }
}
