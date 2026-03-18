<?php
namespace Gametech\Payment\Helpers;

class WebhookHelper
{
    public static function verifyWebhook(array $data, string $apiKey, string $secretKey): bool
    {
        if (!isset($data['transactionId']) || !isset($data['hash'])) {
            return false;
        }

        $password = $apiKey . $secretKey;

        try {
            $decryptedTransactionId = self::decryptAESOpenSSL($data['hash'], $password);
            return $decryptedTransactionId === $data['transactionId'];
        } catch (\Exception $e) {
            return false;
        }
    }

    private static function decryptAESOpenSSL(string $encryptedBase64, string $password): string
    {
        $encrypted = base64_decode($encryptedBase64);

        $saltedPrefix = substr($encrypted, 0, 8);
        if ($saltedPrefix !== "Salted__") {
            throw new \Exception("ไม่พบ Salted__ prefix");
        }

        $salt = substr($encrypted, 8, 8);
        $ct = substr($encrypted, 16);

        $salted = '';
        $dx = '';
        while (strlen($salted) < 48) {
            $dx = md5($dx . $password . $salt, true);
            $salted .= $dx;
        }

        $key = substr($salted, 0, 32);
        $iv = substr($salted, 32, 16);

        $decrypted = openssl_decrypt($ct, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new \Exception("ถอดรหัสล้มเหลว");
        }

        return $decrypted;
    }
}
