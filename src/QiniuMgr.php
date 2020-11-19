<?php


namespace Lettered\Support;

use Lettered\Support\Exceptions\EvidentException;
use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

/**
 * 七牛处理类
 * Class QiniuMgr
 * @package Lettered\Support
 */
class QiniuMgr
{
    protected $config = [];

    private $auth = null;

    /**
     * QiniuMgr constructor.
     * @param $config
     * @throws EvidentException
     */
    public function __construct($config)
    {
        if (empty($config)){
            throw new EvidentException([
               'errmsg' => '[qiniu] init fail: config empty'
            ]);
        }
        $this->config = $config;
        // 构建鉴权对象
        $this->auth = new Auth($this->config['accessKey'],$this->config['secretKey']);
    }

    /**
     * Upload
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/9/20 15:44
     *
     * @param string $filename 写入文件名称
     * @param resource $source 资源数据
     * @return string|mixed
     * @throws \Exception
     * @throws EvidentException
     */
    public function upload($filename, $source){
        // 生成上传需要的token
        $token = $this->auth->uploadToken($this->config['bucket']);
        // 初始化UploadManager类
        $uploadMgr = new UploadManager();
        list($res ,$err) = $uploadMgr->putFile($token, $filename, $source);

        if($err !== null){
            throw new EvidentException([
                'errmsg' => "[qiniu] upload fail:" . $err
            ]);
        }else{
            return true;
        }
    }
    /**
     * 移动文件
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/9/20 15:41
     *
     * @param string $key   旧命名
     * @param string $nkey  新命名
     * @return string|boolean
     * @throws EvidentException
     */
    public function move($key, $nkey){

        $bucketManager = new BucketManager($this->auth, new Config());

        $err = $bucketManager->move($this->config['bucket'], $key, $this->config['bucket'], $nkey, true);
        if($err !== null){
            throw new EvidentException([
                'errmsg' => "[qiniu] move fail:" . $err
            ]);
        }else{
            return true;
        }
    }

    /**
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/9/20 17:13
     *
     * @param string $key 文件名称
     * @return mixed|bool
     * @throws EvidentException
     */
    public function delete($key){

        $bucketManager = new BucketManager($this->auth, new Config());

        $err = $bucketManager->delete($this->config['bucket'], $key); // 设置0 直接无 生命周期
        return true;
        if($err !== null){
            throw new EvidentException([
                'errmsg' => "[qiniu] delete fail: 文件已删除或移动"
            ]);
        }else{
            return true;
        }
    }

}