<?php


namespace Lettered\Support\Exceptions;

use Exception;
use think\exception\Handle;

class ExceptionHandle extends Handle
{
    private $code;
    private $errmsg;
    private $errcode;

    /**
     * 异常处理接管
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/16 12:08
     *
     * @param Exception $e
     * @return \think\Response|\think\response\Json
     */
    public function render(Exception $e)
    {
        if ($e instanceof BaseException) {
            //如果是自定义异常，则控制http状态码，不需要记录日志
            //因为这些通常是因为客户端传递参数错误或者是用户请求造成的异常
            //不应当记录日志

            $this->code = $e->code;
            $this->errmsg = $e->errmsg;
            $this->errcode = $e->errcode;
        } else {
            // 如果是服务器未处理的异常，将http状态码设置为500，并记录日志
            $this->code = 500;
            $this->errcode = 90009;
            $this->errmsg = 'Global: Please contact a technician for help!';

            if (config('app_debug')) {
                // 调试状态下需要显示TP默认的异常页面，因为TP的默认页面
                // 很容易看出问题
                $this->errmsg = $e->getMessage();

                return parent::render($e);
            }
        }
        app()->log('[' .  $this->errcode . ']' .  $this->errmsg,'error');
        return json([
            'errcode' => $this->errcode,
            'errmsg' => $this->errmsg
        ])->code(200);
    }
}