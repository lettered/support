<?php


namespace Lettered\Support;


use Lettered\Support\Exceptions\FailedException;

/**
 * //所有接口展示
    //获取所有的文件名称,含完整路径
    //FILE::init($path)->getRawFiles();
    //获取所有的文件名称,不含路径
    //FILE::init($path)->getFiles();
    //获取所有的文件夹名称,含完整路径
    // FILE::init($path)->getRawDirs();
    //获取所有的文件夹名称,不含路径
    // FILE::init($path)->getDirs();
    //获取目标文件夹$path的树形结构图
    // FILE::init($path)->getTree();
    //获取目标文件夹$path的信息
    // FILE::init($path)->getInfo();
    //把$path下的所有文件复制到$target目录下,如果有指定类型的情况下,那么只复制指定类型的文件
    // FILE::init($path)->copyFiles($target, 'php');
    //把$path下的所有文件夹复制到$target目录下,并且按$path的层级摆放
    // FILE::init($path)->copyDirs($target);
    //把$path下的所有文件剪切到$taret目录下,如果有指定类型的情况下,那么只移动指定类型的文件
    // FILE::init($path)->moveFiles($target, 'php');
    //把$path下的所有文件及文件夹移动到$target目录下,并且不改变原有的层级结构
    // FILE::init($path)->moveAll($target);
    //删除指定文件夹下的所有文件,不含文件夹
    // FILE::init($path)->removeFiles();
    //删除指定路径下的所有内容含文件,文件夹
    // FILE::init($path)->removeAll();
 * @package Lettered\Support
 */
class File
{
    /**
     * @var string
     */
    private static $path;

    /**
     * @var array
     */
    private static $files = [];

    /**
     * @var array
     */
    private static $dirs = [];

    /**
     * File constructor.
     * @param string $path 路径
     * @throws FailedException
     */
    private function __construct($path)
    {
        try {
            if (is_dir($path)) {
                self::$path = strtr($path, ['\\' => '/']);
            }
        } catch (\Exception $e) {
            throw new FailedException([
                'errmsg' => $e->getMessage()
            ]);
        }
    }

    /**
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:22
     *
     * @param string $path
     * @return array
     */
    private function runFiles($path)
    {
        $arr = ['files' => [], 'dirs' => [], 'all' => []];
        $target = array_diff(scandir($path), ['.', '..']);
        array_walk($target, function ($val, $key) use (&$arr, $path) {
            $subTarget = "{$path}/{$val}";
            if (is_file($subTarget)) {
                array_push($arr['files'], "{$path}/" . $val);
            } else if (is_dir($subTarget)) {
                array_push($arr['dirs'], "{$path}/" . $val);
                // 再次取下级文件夹
                // $arr = array_merge_recursive($arr, $this->runFiles($subTarget));
            }
        });
        return $arr;
    }

    /**
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/20 10:21
     *
     * @param $dir
     * @return mixed
     */
    public function mkdir($dir)
    {
        return self::createDir(self::$path . $dir);
    }

    /**
     * 创建文件
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/20 11:09
     *
     * @param $file
     * @return bool
     */
    public function touch($file)
    {
        return touch(self::$path . $file);
    }

    /**
     * 新建文件夹,如果目标文件夹不存在的情况下
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:23
     *
     * @param string $target
     * @return mixed
     */
    private static function createDir($target)
    {
        if (!is_dir($target)) {
            mkdir($target, 0777, true);
        }
        return $target;
    }

    /**
     * 判断是否是空的文件夹
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:23
     *
     * @param string $dir
     * @return bool
     */
    private static function isEmptyDir($dir)
    {
        $arr = array_diff(scandir($dir), ['.', '..']);
        return count($arr) == 0 ? true : false;
    }

    /**
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:25
     *
     * @param string $path
     * @return File
     * @throws FailedException
     */
    public static function init($path)
    {
        $cls = new self($path);
        $all = $cls->runFiles(self::$path);
        self::$files = $all['files'];
        self::$dirs = $all['dirs'];
        return $cls;
    }

    /**
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:26
     *
     * @param string $target
     * @param string $mode
     * @param string $extension
     * @return int
     */
    private function dealFile($target, $mode, $extension)
    {
        $target = self::createDir($target);
        $result = 0;
        array_walk(self::$files, function ($val) use ($target, $extension, $mode, &$result) {
            $info = pathinfo($val);
            if (!$extension || ($extension && strcasecmp($info['extension'], $extension) == 0)) {
                $res = strcasecmp($mode, 'move') == 0 ? rename($val, $target . '/' . $info['basename']) : copy($val, $target . '/' . $info['basename']);
                if ($res) {
                    $result++;
                }
            }
        });
        return $result;
    }

    /**
     * 获取真实的文件路径
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:27
     *
     * @return array
     */
    public function getRawFiles()
    {
        return self::$files;
    }

    /**
     * 获取真实的文件夹路径
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:27
     *
     * @return array
     */
    public function getRawDirs()
    {
        return self::$dirs;
    }

    /**
     * 读取文件内容
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/21 10:46
     *
     * @param string $name 文件名称
     * @return string
     */
    public function getFile($name)
    {
        // 读取文件信息
        $file_path = self::$path . $name;
        $handle = fopen($file_path, "r");
        // 读取文件长度
        $len = filesize($file_path);
        $contents = '';
        if ($len !== 0) {
            while (!feof($handle)) {
                $contents .= fread($handle, $len);
            }
        }
        fclose($handle);
        return $contents;
    }

    /**
     * 获取全部的文件名
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:27
     *
     * @return array
     */
    public function getFiles()
    {
        $arr = [];
        array_walk(self::$files, function ($val) use (&$arr) {
            array_push($arr, basename($val));
        });
        return $arr;
    }

    /**
     * 获取当前路径下所有的文件夹
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:27
     *
     * @return array
     */
    public function getDirs()
    {
        $arr = [];
        array_walk(self::$dirs, function ($val) use (&$arr) {
            array_push($arr, basename($val));
        });
        return $arr;

    }

    /**
     * 获取当前路径下树形结构图,注意这边的引用传值
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:28
     *
     * @return array
     */
    public function getTree()
    {
        $all = array_merge(self::$dirs, self::$files);
        $tree = [];
        $diff = explode('/', self::$path);
        if ($all) {
            array_walk($all, function ($val) use ($diff, &$tree) {
                $temp_arr = explode('/', $val);
                if (is_file($val)) {
                    $file = end($temp_arr);
                    array_push($diff, $file);
                }
                $temp_arr = array_diff($temp_arr, $diff);
                $parent =& $tree;
                foreach ($temp_arr as $k => $v) {
                    if (isset($parent[$v])) {
                        $parent[$v] = [];
                    }
                    $parent =& $parent[$v];
                }
                if (is_file($val)) {
                    array_push($parent, $file);
                }
            });
        }
        return $tree;
    }

    /**
     * 当前路径下所有文件以及文件夹
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 23:09
     *
     * @return array
     */
    public function getAll()
    {
        return array_merge($this->getDirs(), $this->getFiles());
    }

    /**
     * 展示文件夹的信息
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:28
     *
     * @return array
     */
    public function getInfo()
    {
        $files = self::$files;
        $dirs = self::$dirs;
        $size = 0;
        array_walk($files, function ($val) use (&$size) {
            $size += filesize($val);
        });
        return [
            'size' => $size,
            'dirs' => count($dirs),
            'files' => count($files)
        ];
    }

    /**
     * 进行文件拷贝
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:32
     *
     * @param string $target 目标文件夹
     * @param null $type
     * @return int
     */
    public function copyFiles($target, $type = null)
    {
        return $this->dealFile($target, 'copy', $type);
    }

    /**
     * 复制所有的空文件夹
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/20 10:42
     *
     * @param $target
     * @return int
     */
    public function copyDirs($target)
    {
        $dirs = self::$dirs;
        $target = strtr(trim($target), ['\\' => '/']);
        $target_arr = explode('/', $target);
        if (end($target_arr) == '') {
            array_pop($target_arr);
        }
        $diff = explode('/', self::$path);
        $count = 0;
        array_walk($dirs, function ($val) use (&$count, $target_arr, $diff) {
            $temp_arr = array_diff(explode('/', $val), $diff);
            $new_path = implode('/', $target_arr) . '/' . implode('/', $temp_arr);
            if (!is_dir($new_path) && mkdir($new_path, 0777, true)) {
                $count++;
            }
        });
        return $count;
    }

    /**
     * 文件的剪切
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:28
     *
     * @param string $target 目标路径
     * @param null $type
     * @return int
     */
    public function moveFiles($target, $type = null)
    {
        return $this->dealFile($target, 'move', $type);
    }

    /**
     * 剪切所有的文件夹以及文件
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:30
     *
     * @param string $target 目标文件夹
     * @return array
     */
    public function moveAll($target = '../resource/refuse/')
    {
        $dirs = $this->copyDirs($target . self::$path);
        $files = self::$files;

        $target_arr = explode('/', $target . self::$path);
        if (end($target_arr) == '') {
            array_pop($target_arr);
        }
        $diff = explode('/', self::$path);

        $count = 0;
        array_walk($files, function ($val) use (&$count, $target_arr, $diff) {
            $temp_arr = array_diff(explode('/', $val), $diff);
            $new_path = implode('/', $target_arr) . '/' . implode('/', $temp_arr);
            if (rename($val, $new_path)) {
                $count++;
            }
        });
        $this->removeAll();
        rmdir(self::$path);
        return [
            'files' => $count,
            'dirs' => $dirs
        ];
    }

    /**
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/21 13:12
     *
     * @param $name
     * @param $newname
     * @return bool
     */
    public function renameFile($name, $newname)
    {
        return rename(self::$path . $name, self::$path . $newname);
    }

    /**
     * 写入文件
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/21 13:16
     *
     * @param $name
     * @param $content
     * @return false|int
     */
    public function changeFile($name, $content)
    {
        $handle= fopen(self::$path . $name, "w");  //w是写入模式，文件不存在则创建文件写入。
        $len = fwrite($handle, $content);
        fclose($handle);
        return $len;
    }

    /**
     * 删除文件
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/20 10:43
     *
     * @param $file
     * @param bool  $force 是否强制删除
     * @return bool
     */
    public function removeFile($file, $force = false)
    {
        if (file_exists(self::$path . $file)) {

            if ($force === true) {
                unlink(self::$path . $file);
            } elseif (is_string($force)) {
                // 转移目标路径
                copy(self::$path . $file, $force);
            } else {
                $newDir = '../resource/refuse/' . self::$path;
                if (!is_dir($newDir)) {
                    mkdir($newDir, 0777, true);
                }
                // 默认删除移动路径
                copy(self::$path . $file, $newDir . $file);
            }
            // 删除原有
            unlink(self::$path . $file);
        }

        return true;
    }

    /**
     * 删除指定目录下的所有文件
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:30
     *
     * @return int
     */
    public function removeFiles()
    {
        $count = 0;
        array_walk(self::$files, function ($val) use (&$count) {
            if (file_exists($val) && unlink($val)) {
                $count++;
            }
        });
        return $count;
    }

    /**
     * 进行删除文件夹所有内容的操作
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 21:31
     *
     * @return bool
     */
    public function removeAll()
    {
        $dirs = self::$dirs;
        //进行文件夹排序
        uasort($dirs, function ($m, $n) {
            return strlen($m) > strlen($n) ? -1 : 1;
        });
        //删除所有文件
        $this->removeFiles();
        array_walk($dirs, function ($val) {
            rmdir($val);
        });
        return self::isEmptyDir(self::$path);
    }
}