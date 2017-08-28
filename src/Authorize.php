<?php

namespace Pegziq\WorkWeChat;

use Pegziq\WorkWeChat\Utils\Bag;

/**
 * OAuth 成员登录授权获取用户信息.
 */
class Authorize
{
    const API_USER = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_login_info';
    const API_URL = 'https://qy.weixin.qq.com/cgi-bin/loginpage';
    /**
     * 应用ID.
     *
     * @var string
     */
    protected $appId;
    /**
     * 应用secret.
     *
     * @var string
     */
    protected $appSecret;
    /**
     * Http对象
     *
     * @var Http
     */
    protected $http;
    /**
     * 输入.
     *
     * @var Bag
     */
    protected $input;

    /**
     * constructor.
     *
     * @param string $appId
     * @param string $appSecret
     */
    public function __construct($appId, $appSecret)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->http = new Http(new AccessToken($appId, $appSecret));
        $this->input = new Input();
    }

    /**
     * 通过授权获取用户.
     *
     * @param string $to
     * @param string $state
     * @param string $usertype
     *
     * @return array | null
     */
    public function authorize($to = null, $state = 'STATE', $usertype = 'all')
    {
        if (!$this->input->get('state') && !$this->input->get('auth_code')) {
            $this->redirect($to, $state, $usertype);
        }

        return $this->user();
    }

    /**
     * 直接跳转.
     *
     * @param string $to
     * @param string $state
     * @param string $usertype
     */
    public function redirect($to = null, $state = null, $usertype = null)
    {
        $state = $state ? $state : md5(time());
        header('Location:' . $this->url($to, $state, $usertype));

        exit;
    }

    /**
     * 生成outh URL.
     *
     * @param string $to
     * @param string $state
     * @param string $usertype
     *
     * @return string
     */
    public function url($to = null, $state = 'STATE', $usertype = 'all')
    {
        $to !== null || $to = Url::current();

        $params = array(
            'corp_id' => $this->appId,
            'redirect_uri' => $to,
            'state' => $state,
            'usertype' => $usertype
        );

        return self::API_URL . '?' . http_build_query($params);
    }

    /**
     * 获取企业号登录用户信息.
     * @return array
     */
    public function user()
    {
        return $this->http->jsonPost(self::API_USER, array('auth_code' => $this->input->get('auth_code')));
    }
}
