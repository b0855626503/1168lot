<?php


namespace App\Libraries;

class Agent
{
    private $AESKey;
    private $MD5Key;
    public $Url;

    public function __construct($pAESKey, $pMD5Key, $pUrl="") {
        $this->AESKey = $pAESKey;
        $this->MD5Key = $pMD5Key;
        $this->Url = $pUrl;
    }

    public function prepareSign($data) {

        if(preg_match('#/v1/accountcreate#',$this->Url)) {
            return json_encode($data);
        }

        return '';
    }

    public function sign($plainData): string
    {
        $encrypt = Security::encrypt($plainData, $this->AESKey);
        $data = base64_encode($encrypt);
        return md5($data.$this->MD5Key);
    }
}
