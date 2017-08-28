<?php

namespace Stoneworld\Wechat\Messages;

/**
 * 文本消息
 *
 * @property string $content
 */
class TextCard extends BaseMessage
{

    /**
     * 属性
     *
     * @var array
     */
    protected $properties = array('title','description','url');

    /**
     * 生成主动消息数组
     *
     * @return array
     */
    public function toStaff()
    {
        return array(
                'textcard' => array(
                           'title' => $this->title,
                           'description' => $this->description,
                           'url' => $this->url,
                          ),
               );
    }

    /**
     * 生成回复消息数组
     *
     * @return array
     */
    public function toReply()
    {
        return array(
                'title' => $this->title,
                'description' => $this->description,
                'url' => $this->url,
               );
    }
}
