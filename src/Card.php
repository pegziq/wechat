<?php

namespace Pegziq\WorkWeChat;

use Pegziq\WorkWeChat\Utils\Arr;
use Pegziq\WorkWeChat\Utils\Bag;
use Pegziq\WorkWeChat\Utils\JSON;

/**
 * 卡券.
 */
class Card
{
    /**
     * Http对象
     *
     * @var Http
     */
    protected $http;

    /**
     * Cache对象
     *
     * @var Cache
     */
    protected $cache;

    /**
     * js ticket.
     *
     * @var string
     */
    protected $ticket;

    // 卡券类型
    const TYPE_GENERAL_COUPON = 'GENERAL_COUPON';   // 通用券
    const TYPE_GROUPON = 'GROUPON';          // 团购券
    const TYPE_DISCOUNT = 'DISCOUNT';         // 折扣券
    const TYPE_GIFT = 'GIFT';             // 礼品券
    const TYPE_CASH = 'CASH';             // 代金券

    const CARD_STATUS_NOT_VERIFY = 'CARD_STATUS_NOT_VERIFY';   // 待审核
    const CARD_STATUS_VERIFY_FAIL = 'CARD_STATUS_VERIFY_FAIL';   //审核失败
    const CARD_STATUS_VERIFY_OK = 'CARD_STATUS_VERIFY_OK';     //通过审核
    const CARD_STATUS_USER_DELETE = 'CARD_STATUS_USER_DELETE';   //卡券被商户删除
    const CARD_STATUS_USER_DISPATCH = 'CARD_STATUS_USER_DISPATCH'; //在公众平台投放过的卡券 

    const API_CREATE = 'https://qyapi.weixin.qq.com/cgi-bin/card/create';  // 创建卡券
    const API_DELETE = 'https://qyapi.weixin.qq.com/cgi-bin/card/delete';  // 删除卡券
    const API_GET = 'https://qyapi.weixin.qq.com/cgi-bin/card/get';  // 获取卡券详情
    const API_LIST = 'https://qyapi.weixin.qq.com/cgi-bin/card/batchget';  // 获取卡券摘要列表
    const API_CONSUME = 'https://qyapi.weixin.qq.com/cgi-bin/card/code/consume';  // 核销code
    const API_CODE_GET = 'https://qyapi.weixin.qq.com/cgi-bin/card/code/get';  // 查询code
    const API_UPDATE_STOCK = 'https://qyapi.weixin.qq.com/cgi-bin/card/modifystock';  // 修改卡券库存
    const API_TICKET = 'https://qyapi.weixin.qq.com/cgi-bin/ticket/get';  // 获取api_ticket

    /**
     * constructor.
     *
     * @param string $appId
     * @param string $appSecret
     */
    public function __construct($appId, $appSecret)
    {
        $this->http = new Http(new AccessToken($appId, $appSecret));
        $this->cache = new Cache($appId);
    }

    /**
     * 获取jsticket.
     *
     * @return string
     */
    public function getTicket()
    {
        if ($this->ticket) {
            return $this->ticket;
        }

        $key = 'stoneworld.wechat.card.api_ticket';

        // for php 5.3
        $http = $this->http;
        $cache = $this->cache;
        $apiTicket = self::API_TICKET;

        return $this->ticket = $this->cache->get(
            $key,
            function ($key) use ($http, $cache, $apiTicket) {
                $result = $http->get($apiTicket);

                $cache->set($key, $result['ticket'], $result['expires_in']);

                return $result['ticket'];
            }
        );
    }

    /**
     * 生成 js添加到卡包 需要的 card_list 项.
     *
     * @param string $cardId
     * @param array $extension
     *
     * @return string
     */
    public function attachExtension($cardId, array $extension = array())
    {
        $timestamp = time();

        $ext = array(
            'code' => Arr::get($extension, 'code'),
            'openid' => Arr::get($extension, 'openid', Arr::get($extension, 'open_id')),
            'timestamp' => $timestamp,
            'outer_id' => Arr::get($extension, 'outer_id'),
            'balance' => Arr::get($extension, 'balance'),
        );

        $ext['signature'] = $this->getSignature(
            $this->getTicket(),
            $timestamp,
            $cardId,
            $ext['code'],
            $ext['openid'],
            $ext['balance']
        );

        return array(
            'cardId' => $cardId,
            'cardExt' => JSON::encode($ext),
        );
    }

    /**
     * 创建卡券.
     *
     * @param array $base
     * @param array $properties
     * @param string $type
     *
     * @return string
     */
    public function create(array $base, array $properties = array(), $type = self::TYPE_GENERAL_COUPON)
    {
        $key = strtolower($type);
        $card = array_merge(array('base_info' => $base), $properties);
        $params = array(
            'card' => array(
                'card_type' => $type,
                $key => $card,
            ),
        );

        $result = $this->http->jsonPost(self::API_CREATE, $params);

        return $result['card_id'];
    }

    /**
     * 卡券详情.
     *
     * @param string $cardId
     *
     * @return Bag
     */
    public function get($cardId)
    {
        $params = array('card_id' => $cardId);

        $result = $this->http->jsonPost(self::API_GET, $params);

        return new Bag($result['card']);
    }

    /**
     * 批量获取卡券列表.
     *
     * @param int $offset
     * @param int $count
     * @param array $statusList
     *
     * @return array
     */
    public function lists($offset = 0, $count = 10, $statusList = array())
    {
        $params = array(
            'offset' => $offset,
            'count' => $count,
            'status_list' => $statusList,
        );

        $result = $this->http->jsonPost(self::API_LIST, $params);

        return $result['card_id_list'];
    }

    /**
     * 核销
     *
     * @param string $code 要消耗序列号
     * @param string $cardId 卡券 ID。创建卡券时 use_custom_code 填写 true 时必填。
     *                       非自定义 code 不必填写。
     *
     * @return Bag
     */
    public function consume($code, $cardId = null)
    {
        $params = array(
            'code' => $code,
            'card_id' => $cardId,
        );

        return new Bag($this->http->jsonPost(self::API_CONSUME, $params));
    }

    /**
     * 删除卡券.
     *
     * @param string $cardId
     *
     * @return bool
     */
    public function delete($cardId)
    {
        $params = array('card_id' => $cardId);

        return $this->http->jsonPost(self::API_DELETE, $params);
    }

    /**
     * 修改库存.
     *
     * @param string $cardId
     * @param int $amount
     *
     * @return bool
     */
    public function updateStock($cardId, $amount)
    {
        if (!$amount) {
            return true;
        }

        $key = $amount > 0 ? 'increase_stock_value' : 'reduce_stock_value';

        $params = array(
            'card_id' => $cardId,
            $key => abs($amount),
        );

        return $this->http->jsonPost(self::API_UPDATE_STOCK, $params);
    }

    /**
     * 增加库存.
     *
     * @param string $cardId
     * @param int $amount
     *
     * @return bool
     */
    public function incStock($cardId, $amount)
    {
        return $this->updateStock($cardId, abs($amount));
    }

    /**
     * 减少库存.
     *
     * @param string $cardId
     * @param int $amount
     *
     * @return bool
     */
    public function decStock($cardId, $amount)
    {
        return $this->updateStock($cardId, abs($amount) * -1);
    }

    /**
     * 查询Code.
     *
     * @param string $code
     * @param string $cardId
     *
     * @return Bag
     */
    public function getCode($code, $cardId = null)
    {
        $params = array(
            'code' => $code,
            'card_id' => $cardId,
        );

        return new Bag($this->http->jsonPost(self::API_CODE_GET, $params));
    }

    /**
     * 生成签名.
     *
     * @return string
     */
    public function getSignature()
    {
        $params = func_get_args();

        sort($params, SORT_STRING);

        return sha1(implode($params));
    }

    /**
     * 获取随机字符串.
     *
     * @return string
     */
    public function getNonce()
    {
        return uniqid('pre_');
    }
}
