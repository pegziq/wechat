<?php

namespace Pegziq\WorkWeChat\Messages;

use Pegziq\WorkWeChat\Media;

/**
 * 声音消息
 *
 * @property string $media_id
 */
class Voice extends BaseMessage
{

    /**
     * 属性
     *
     * @var array
     */
    protected $properties = array('media_id');

    /**
     * 媒体
     *
     * @var \Pegziq\WorkWeChat\Media
     */
    protected $media;

    /**
     * 设置语音
     *
     * @param string $mediaId
     *
     * @return Voice
     */
    public function media($mediaid)
    {
        $this->setAttribute('media_id', $mediaid);

        return $this;
    }

    /**
     * 生成主动消息数组
     *
     * @return array
     */
    public function toStaff()
    {
        return array(
                'voice' => array(
                            'media_id' => $this->media_id,
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
                'Voice' => array(
                            'MediaId' => $this->media_id,
                           ),
               );
    }
}
