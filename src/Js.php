<?php

namespace Pegziq\WorkWeChat;

use Pegziq\WorkWeChat\Utils\JSON;

/**
 * 微信 JSSDK.
 */
class Js
{
    const API_TICKET = 'https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket';
    const API_TICKET_CONTACT = 'https://qyapi.weixin.qq.com/cgi-bin/ticket/get?type=contact';
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
     * Cache对象
     *
     * @var Cache
     */
    protected $cache;
    /**
     * 当前URL.
     *
     * @var string
     */
    protected $url;

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
        $this->cache = new Cache($appId);
    }

    /**
     * 获取数组形式的配置.
     *
     * @param array $APIs
     * @param bool $debug
     * @param bool $beta
     * @param bool $isAdminGroup
     *
     * @return array
     */
    public function getConfigArray(array $APIs, $debug = false, $beta = false, $isAdminGroup = false)
    {
        return $this->config($APIs, $debug, $beta, false, $isAdminGroup);
    }

    /**
     * 获取JSSDK的配置数组.
     *
     * @param array $APIs
     * @param bool $debug
     * @param bool $json
     * @param bool $isAdminGroup
     *
     * @return string|array
     */
    public function config(array $APIs, $debug = false, $beta = false, $json = true, $isAdminGroup = false)
    {
        $signPackage = $isAdminGroup ? $this->getSignaturePackage(null, null, null, true) : $this->getSignaturePackage();
        $base = array(
            'debug' => $debug,
            'beta' => $beta,
        );
        $config = $isAdminGroup ? $signPackage : array_merge($base, $signPackage, array('jsApiList' => $APIs));

        return $json ? JSON::encode($config) : $config;
    }

    /**
     * 签名.
     *
     * @param string $url
     * @param string $nonce
     * @param int $timestamp
     * @param bool $isAdminGroup
     *
     * @return array
     */
    public function getSignaturePackage($url = null, $nonce = null, $timestamp = null, $isAdminGroup = false)
    {
        $url = $url ? $url : $this->getUrl();
        $nonce = $nonce ? $nonce : $this->getNonce();
        $timestamp = $timestamp ? $timestamp : time();
        $ticket = $isAdminGroup ? $this->getGroupTicket() : $this->getTicket();

        $sign = $isAdminGroup ? array(
            'groupId' => $ticket['group_id'],
            'timestamp' => $timestamp,
            'nonceStr' => $nonce,
            'signature' => $this->getSignature($ticket['ticket'], $nonce, $timestamp, $url, true),
        ) : array(
            'appId' => $this->appId,
            'nonceStr' => $nonce,
            'timestamp' => $timestamp,
            'url' => $url,
            'signature' => $this->getSignature($ticket, $nonce, $timestamp, $url),
        );

        return $sign;
    }

    /**
     * 获取当前URL.
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->url) {
            return $this->url;
        }

        return Url::current();
    }

    /**
     * 设置当前URL.
     *
     * @param string $url
     *
     * @return Js
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * 获取随机字符串.
     *
     * @return string
     */
    public function getNonce()
    {
        return uniqid('rand_');
    }

    /**
     * 获取groupticket.
     *
     * @return string
     */
    public function getGroupTicket()
    {
        $key = 'stoneworld.wechat.group_ticket.' . $this->appId;

        // for php 5.3
        $appId = $this->appId;
        $appSecret = $this->appSecret;
        $cache = $this->cache;
        $apiTicket = self::API_TICKET_CONTACT;

        return $this->cache->get(
            $key,
            function ($key) use ($appId, $appSecret, $cache, $apiTicket) {
                $http = new Http(new AccessToken($appId, $appSecret));

                $result = $http->get($apiTicket);

                $cache->set($key, $result, $result['expires_in']);

                return $result;
            }
        );
    }

    /**
     * 获取jsticket.
     *
     * @return string
     */
    public function getTicket()
    {
        $key = 'stoneworld.wechat.jsapi_ticket.' . $this->appId;

        // for php 5.3
        $appId = $this->appId;
        $appSecret = $this->appSecret;
        $cache = $this->cache;
        $apiTicket = self::API_TICKET;

        return $this->cache->get(
            $key,
            function ($key) use ($appId, $appSecret, $cache, $apiTicket) {
                $http = new Http(new AccessToken($appId, $appSecret));

                $result = $http->get($apiTicket);

                $cache->set($key, $result['ticket'], $result['expires_in']);

                return $result['ticket'];
            }
        );
    }

    /**
     * 生成签名.
     *
     * @param string $ticket
     * @param string $nonce
     * @param int $timestamp
     * @param string $url
     * @param bool $isAdminGroup
     *
     * @return string
     */
    public function getSignature($ticket, $nonce, $timestamp, $url, $isAdminGroup = false)
    {
        return $isAdminGroup ? sha1("group_ticket={$ticket}&noncestr={$nonce}&timestamp={$timestamp}&url={$url}") : sha1("jsapi_ticket={$ticket}&noncestr={$nonce}&timestamp={$timestamp}&url={$url}");
    }
}
