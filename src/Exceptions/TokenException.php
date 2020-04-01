<?php


namespace Lettered\Support\Exceptions;


class TokenException extends BaseException
{
    public $code = 403;
    public $errmsg  = 'Token: Invalid!';
    public $errcode  = 30001;
}