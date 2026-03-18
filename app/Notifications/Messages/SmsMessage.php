<?php

namespace App\Notifications\Messages;


class SmsMessage
{

    public $content;

    public $mobile;

    public function __construct($content = '')
    {
        $this->content = $content;
    }

    public function content($content)
    {
        $this->content = $content;

        return $this;
    }

    public function mobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }
}
