<?php


namespace Lettered\Support;


class Goeasy
{
    /**
     * @var string restUrl
     */
    protected $restUrl = 'https://rest-hangzhou.goeasy.io/publish';

    private $appKey = null;

    private $channel = null;

    protected static $instance = null;

    /**
     * Goeasy constructor.
     * @param string $key
     * @param string $channel
     */
    private function __construct($key, $channel)
    {
        $this->appKey = $key;

        $this->channel = $channel;
    }

    /**
     * 单例初始化
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/23 16:05
     *
     * @param $key
     * @param $channel
     * @return Goeasy|null
     */
    public static function init($key, $channel)
    {
        if (is_null(self::$instance)){
            self::$instance = new self($key, $channel);
        }
        return self::$instance;
    }

    /**
     * 消息推送
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/23 16:01
     *
     * @param array $content 消息内容
     * @return bool|string
     */
    public function send($content = [])
    {
        return $this->curlPost($this->restUrl, [
            'token' => $this->appKey,
            'channel' => $this->channel,
            'content' => enjson($content)
        ]);
    }

    /**
     * curlGet
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 18:48
     *
     * @param string $url 请求地址
     * @param array $options 选填
     * @return bool|string
     */
    private static function curlGet($url = '', $options = [])
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * curlPost
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/19 18:48
     *
     * @param string $url 请求url
     * @param string|array $postData 请求数据
     * @param array $options 选填
     * @return bool|string
     */
    private function curlPost($url = '', $postData = '', $options = [])
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 设置请求头
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type:application/x-www-form-urlencoded'
        ]);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}