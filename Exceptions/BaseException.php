<?php

namespace Lettered\Support\Exceptions;

use think\Exception;

class BaseException extends Exception
{
    public $code = 400;
    public $errmsg = 'Evident: Please contact a technician for help!';
    public $errcode = 40001;

    /**
     * BaseException constructor.
     * @param string $message
     */
    public function __construct($message = "")
    {
        if (!is_array($message)) {
            $this->errmsg = $message;
        } else {
            if(array_key_exists('code',$message)){
                $this->code = $message['code'];
            }
            if(array_key_exists('errmsg',$message)){
                $this->errmsg = $message['errmsg'];
            }
            if(array_key_exists('errcode',$message)){
                $this->errcode = $message['errcode'];
            }
        }

    }
}