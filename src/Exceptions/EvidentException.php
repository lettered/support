<?php


namespace Lettered\Support\Exceptions;


class EvidentException extends BaseException
{
    public $errmsg  = 'Evident: Please contact a technician for help!';
    public $errcode  = 40001;
}