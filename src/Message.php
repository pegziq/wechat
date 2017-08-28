<?php

namespace Pegziq\WorkWeChat;

use InvalidArgumentException;

/**
 * 消息
 */
class Message
{

    /**
     * 消息类型
     */
    const TEXT      = 'text';
    const TEXT_CARD = 'textcard';
    const IMAGE     = 'image';
    const VOICE     = 'voice';
    const VIDEO     = 'video';
    const MP_VIDEO  = 'mpvideo';
    const NEWS      = 'news';
    const NEWS_ITEM = 'news_item';
    const MP_NEWS = 'mp_news';


    /**
     * 创建消息实例
     *
     * @param string $type
     *
     * @return mixed
     */
    public static function make($type = self::TEXT)
    {
        if (!defined(__CLASS__.'::'.strtoupper($type))) {
            throw new InvalidArgumentException("Error Message Type '{$type}'");
        }

        $message = "Pegziq\\WorkWeChat\\Messages\\"
                    .str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $type)));

        return new $message();
    }

    /**
     * 魔术访问
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        return call_user_func_array('self::make', array($method, $args));
    }
}
