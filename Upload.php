<?php


namespace Lettered\Support;

// 引入七牛鉴权类
use Qiniu\Auth;
// 引入七牛上传类
use Qiniu\Storage\UploadManager;

class Upload
{
    /**
     * @var array|mixed
     */
    protected $config = [];

    /**
     * @var string
     */
    protected $driver = 'local';

    /**
     * @var string
     */
    protected $path = '';

    /**
     * Auth constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        if (empty($config)){
            $this->config = config('upload.');
        }else{
            $this->config = $config;
        }

    }

    /**
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/20 0:32
     *
     * @param $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/20 0:32
     *
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * 指定驱动
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/20 0:24
     *
     * @param string $driver
     * @return $this
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * 获取驱动
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/20 0:25
     *
     * @return string
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * 上传
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/20 0:22
     *
     * @param $file
     * @return mixed
     */
    public function upload($file)
    {
        // 调用驱动上传--返回文件名称
        $filename = $this->{$this->getDriver()}($file);

        // 写入数据库
        app()->model('common/SystemAnnex')->storeBy([
            'path' => $this->getPath(),
            'url' => $filename,
            'size' => $file->getInfo()['size'],
            'driver' => $this->getDriver()
        ]);
        return true;
    }

    /**
     * 本地上传
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/20 0:40
     *
     * @param $file
     * @return mixed
     */
    private function local($file)
    {
        // 移动到框架应用根目录/uploads/ 目录下
        $info = $file->move( 'uploads' . $this->getPath(),'');
        return $info->getSaveName();
    }

    /**
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/20 0:53
     *
     * @param $file
     * @return string|null
     * @throws \Exception
     */
    private function qiniu($file)
    {
        // 文件后缀
        $ext =  get_file_ext($file->getInfo()['name']);
        // 构建鉴权对象
        $auth = new Auth($this->config['qiniu']['accessKey'],$this->config['qiniu']['secretKey']);

        // 生成上传需要的token
        $token = $auth->uploadToken($this->config['qiniu']['bucket']);

        // 上传到七牛后保存的文件名
        $filename = get_rand_char() .'.'. $ext;


        // 初始化UploadManager类
        $uploadMgr = new UploadManager();
        list($res,$err) = $uploadMgr->putFile($token,$this->getPath() . '/' .$filename,$file->getInfo()['tmp_name']);

        if($err !== null){
            return null;
        }else{
            return $filename;
        }
    }
}