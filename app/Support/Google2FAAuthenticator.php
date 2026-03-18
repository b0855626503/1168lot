<?php


namespace App\Support;

use PragmaRX\Google2FALaravel\Exceptions\InvalidSecretKey;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class Google2FAAuthenticator extends Authenticator
{
    protected function canPassWithoutCheckingOTP()
    {

        if($this->getUser()->superadmin === 'Y')
            if($this->getUser()->level === 9){
                return true;
            }else{
                return false;
            }

        return
            !$this->isEnabled() ||
            $this->noUserIsAuthenticated() ||
            !$this->isActivated() ||
            $this->twoFactorAuthStillValid();
    }

    protected function getGoogle2FASecretKey()
    {
        $secret = $this->getUser()->{$this->config('otp_secret_column')};

        if (is_null($secret) || empty($secret)) {

            throw new InvalidSecretKey('Secret key cannot be empty.');
        }

        return $secret;
    }

}
