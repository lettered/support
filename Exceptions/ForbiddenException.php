<?php

namespace Lettered\Support\Exceptions;


class ForbiddenException extends BaseException
{
    public $code = 403;
    public $errmsg  = 'Forbidden: Invalid!';
    public $errcode  = 50001;
}