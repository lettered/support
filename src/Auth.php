<?php


namespace Lettered\Support;


use Lettered\Support\Exceptions\FailedException;
use thans\jwt\facade\JWTAuth;
use think\facade\Session;
use think\facade\Config;

class Auth
{

    /**
     * @var array
     */
    protected $auth;

    /**
     * @var
     */
    protected $guard = 'admin';

    /**
     * Auth constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        if (!empty($config)){
            $this->auth = $config;
        }else
            $this->auth = Config::get('dora.auth');

        $this->guard = $this->auth['default']['guard'];
    }

    /**
     * guard
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/16 11:53
     *
     * @param $guard
     * @return $this
     */
    public function guard($guard = 'admin')
    {
        $this->guard = $guard;

        return $this;
    }

    /**
     * attempt
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/16 11:47
     *
     * @param $condition
     * @return mixed
     * @throws FailedException
     */
    public function attempt($condition)
    {
        $user = $this->authenticate($condition);
        // 用户存在性
        if (!$user) {
            throw new FailedException([
                'errmsg' => '不存在该用户！'
            ]);
        }
        // 验证密码
        if (!password_verify($condition['password'], $user->password)) {
            throw new FailedException([
                'errmsg' => '用户密码无效！'
            ]);
        }
        // 记录用户登录
        $user->last_login_ip = request()->ip();
        $user->last_login_time = time();
        $user->save();

        return $this->{$this->getDriver()}($user);
    }

    /**
     * user
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/16 11:44
     *
     * @return mixed
     * @throws FailedException
     */
    public function user()
    {
        switch ($this->getDriver()) {
            case 'jwt':
                $model = app($this->getProvider()['model']);
                $user = $model->where($model->getPk(), JWTAuth::auth()[$this->jwtKey()])->find();
                // 2020/09/20 用户被删除的情况
                if(!$user){
                    throw new FailedException([
                        'errmsg' => 'Unauthorized:Request token denied!'
                    ]);
                }
                return $user;
            case 'session':
                return Session::get($this->sessionUserKey(), null);
            default:
                throw new FailedException('user not found');
        }
    }

    /**
     * logout
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/16 11:44
     *
     * @return bool
     * @throws FailedException
     */
    public function logout()
    {
        switch ($this->getDriver()) {
            case 'jwt':
                return true;
            case 'session':
                Session::delete($this->sessionUserKey());
                return true;
            default:
                throw new FailedException('user not found');
        }
    }

    /**
     * jwt
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/16 11:44
     *
     * @param $user
     * @return string
     */
    protected function jwt($user)
    {
        // 2020/09/20 新增用户唯一校验
        $uniqueId = base64_encode(Str::enjson(request()->cookie()));
        // token
        $token = JWTAuth::builder([$this->jwtKey() => $user->id,'guard' => $this->guard,'unique_id' => $uniqueId]);
        // 写入唯一ID   门面-标志-用户
        session($this->guard . '_unique_id_' . $user->id, $uniqueId);

        JWTAuth::setToken($token);

        return $token;
    }

    /**
     * session
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/16 11:44
     *
     * @param $user
     */
    protected function session($user)
    {
        Session::set($this->sessionUserKey(), $user);
    }

    /**
     * sessionUserKey
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/16 11:44
     *
     * @return string
     */
    protected function sessionUserKey()
    {
        return $this->guard . '_user';
    }

    /**
     * jwtKey
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/16 11:44
     *
     * @return string
     */
    protected function jwtKey()
    {
        return $this->guard . '_id';
    }

    /**
     * getDriver
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/16 11:45
     *
     * @return mixed
     */
    protected function getDriver()
    {
        return $this->auth['guards'][$this->guard]['driver'];
    }

    /**
     * getProvider
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/16 11:46
     *
     * @return mixed
     */
    protected function getProvider()
    {
        return $this->auth['providers'][$this->auth['guards'][$this->guard]['provider']];
    }

    /**
     * authenticate
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/16 11:46
     *
     * @param $condition
     * @return mixed
     */
    protected function authenticate($condition)
    {
        $provider = $this->getProvider();

        return $this->{$provider['driver']}($condition);
    }

    /**
     * orm
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/16 11:46
     *
     * @param $condition
     * @return mixed
     */
    protected function orm($condition)
    {
        return app($this->getProvider()['model'])->where($this->filter($condition))->find();
    }

    /**
     * filter where
     *
     * @author 许祖兴 < zuxing.xu@lettered.cn>
     * @date 2020/3/16 11:45
     *
     * @param $condition
     * @return array
     */
    protected function filter($condition): array
    {
        $where = [];

        // 这里有待处理
        foreach ($condition as $field => $value) {
            if ($field == 'captcha') continue;
            if ($field != 'password')
                $where[$this->auth['field']] = preg_replace('# #','',$value);
        }
        return $where;
    }
}