<?php

namespace Lettered\Support;

use Lettered\Exceptions\EvidentException;
use think\File;

class Uploader
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
     * @var string
     */
    protected $saveName = "";

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
     * 指定存储名
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/7/2 10:05
     *
     * @param $name
     * @return $this
     */
    public function setName($name){

        $this->saveName = $name;

        return $this;
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
     * 自己的验证
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/9/20 19:28
     *
     * @param File $file
     * @return mixed
     * @throws EvidentException
     */
    private function validate(File $file)
    {
        // 文件信息
        $fileinfo = $file->getInfo();
        // 文件后缀
        $ext =  get_file_ext($fileinfo['name']);
        // 组合上传类型
        $extension = $this->config['upload_file_ext'] . "," . $this->config['upload_image_ext'];
        // 格式和大小检查
        if ($fileinfo['size'] > (int)($this->config['upload_file_size'] ?: 1e9) * 1024){
            throw new EvidentException([
                'errmsg' => '[local] upload fail: 上传文件大小超过限制'
            ]);
        }
        if (!in_array($ext, str2arr($extension))){
            throw new EvidentException([
                'errmsg' => '[local] upload fail: 不支持的上传文件类型'
            ]);
        }
        return $fileinfo;
    }
    /**
     * 上传
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/20 0:22
     *
     * @param File $file
     * @return mixed
     */
    public function upload(File $file)
    {
        // 调用驱动上传--返回文件名称
        $filename = $this->{$this->getDriver()}($file);
        return [
            'path' => $this->getPath(),
            'url' => $filename,
            'size' => $file->getInfo()['size'],
            'driver' => $this->getDriver()
        ];
    }

    /**
     * 本地上传
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/20 0:40
     *
     * @param File $file
     * @return mixed
     * @throws EvidentException
     */
    private function local(File $file)
    {
        // 自己先验证一遍
        $this->validate($file);
        // 组合上传类型
        $extension = $this->config['upload_file_ext'] . "," . $this->config['upload_image_ext'];
        // 移动到框架应用根目录/uploads/ 目录下
        $result = $file->validate(['size' => ($this->config['upload_file_size'] ?: 1e9) * 1024,'ext' => $extension])->move(
            'uploads' . $this->getPath(),
            $this->saveName != "" ? $this->saveName : md5(time() . $file->hash())
        );
        if (!$result){
            throw new EvidentException([
               'errmsg' => '[local] upload fail:' . $file->getError()
            ]);
        }
        return $result->getSaveName();
    }

    /**
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/20 0:53
     *
     * @param File $file
     * @return string|null
     * @throws \Exception
     */
    private function qiniu(File $file)
    {
        // 验证
        $fileinfo = $this->validate($file);
        // 文件后缀
        $ext =  get_file_ext($fileinfo['name']);
        // 上传到七牛后保存的文件名
        $filename = strtolower(get_rand_char()) .'.'. $ext;
        // 文件夹处理
        $filepath = $this->getPath();
        $filepath =  ($filepath == "/") ? "" : (substr($filepath, 1 ) . "/");

        // QiniuMgr 初始化
        $QiniuMgr = new QiniuMgr($this->config['qiniu']);
        $QiniuMgr->upload($filepath . $filename,$file->getInfo()['tmp_name']);

        return $filename;

    }
}