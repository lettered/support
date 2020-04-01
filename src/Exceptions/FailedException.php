<?php


namespace Lettered\Support\Exceptions;


class FailedException extends BaseException
{
    public $errmsg  = 'Failed: Invalid request!';
    public $errcode  = 90000;
}