<?php

namespace Pegziq\WorkWeChat;

use Pegziq\WorkWeChat\Utils\Bag;

/**
 * OAuth 网页授权获取用户信息.
 */
class Auth
{
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


    const API_USER = 'https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo';
    const API_URL = 'https://open.weixin.qq.com/connect/oauth2/authorize';
    const API_TO_OPENID = 'https://qyapi.weixin.qq.com/cgi-bin/user/convert_to_openid';

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
     * 生成outh URL.
     *
     * @param string $to
     * @param string $scope
     * @param string $state
     *
     * @return string
     */
    public function url($to = null, $args, $state = 'STATE')
    {
        $to !== null || $to = Url::current();
        $args = is_array($args) ? $args : [$args];
        $params = array_merge(array(
            'appid' => $this->appId,
            'redirect_uri' => $to,
            'response_type' => 'code',
            'state' => $state,
        ),$args);

        return self::API_URL.'?'.http_build_query($params).'#wechat_redirect';
    }

    /**
     * 直接跳转.
     *
     * @param string $to
     * @param array $scope
     * @param string $state
     */
    public function redirect($to = null, $scope = array(), $state = null)
    {
        $state = $state ? $state : md5(time());
        header('Location:'.$this->url($to, $scope, $state));

        exit;
    }

    /**
     * 获取用户的openid|userid
     * @return [type] [description]
     */
    public function user()
    {
        return $this->http->get(self::API_USER . '?code=' . $this->input->get('code'));
    }

    /**
     * 通过授权获取用户.
     *
     * @param string $to
     * @param string $state
     * @param string $scope
     *
     * @return array | null
     */
    public function authorize($to = null, $scope = 'snsapi_base', $state = 'STATE')
    {
        if (!$this->input->get('state') && !$this->input->get('code')) {
            $this->redirect($to, $scope, $state);
        }

        return $this->user();
    }

    /**
     * userid转换成openid接口
     * @param  string $userId  userid
     * @param  int    $agentId 应用id
     * @return array
     */
    public function toOpenId($userId, $agentId)
    {
        $params = array(
                'userid' => $userId,
                'agentid' => $agentId,
            );

        return $this->http->jsonPost(self::API_TO_OPENID, $params);
    }
}
