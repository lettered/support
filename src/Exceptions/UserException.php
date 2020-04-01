<?php


namespace Lettered\Support\Exceptions;


class UserException extends BaseException
{
    public $code = 403;
    public $errmsg  = 'User: Invalid!';
    public $errcode  = 20001;
}