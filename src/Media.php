<?php

namespace Pegziq\WorkWeChat;

use Pegziq\WorkWeChat\Utils\Arr;
use Pegziq\WorkWeChat\Utils\Bag;
use Pegziq\WorkWeChat\Utils\File;
use Pegziq\WorkWeChat\Utils\JSON;

/**
 * 媒体素材.
 *
 * @method string image($path)
 * @method string voice($path)
 * @method string thumb($path)
 */
class Media
{
    const API_TEMPORARY_UPLOAD = 'https://qyapi.weixin.qq.com/cgi-bin/media/upload';
    const API_FOREVER_UPLOAD = 'https://qyapi.weixin.qq.com/cgi-bin/material/add_material';
    const API_TEMPORARY_GET = 'https://qyapi.weixin.qq.com/cgi-bin/media/get';
    const API_FOREVER_GET = 'https://qyapi.weixin.qq.com/cgi-bin/material/get';
    const API_FOREVER_NEWS_UPLOAD = 'https://qyapi.weixin.qq.com/cgi-bin/material/add_mpnews';
    const API_FOREVER_NEWS_UPDATE = 'https://qyapi.weixin.qq.com/cgi-bin/material/update_mpnews';
    const API_FOREVER_DELETE = 'https://qyapi.weixin.qq.com/cgi-bin/material/del';
    const API_FOREVER_COUNT = 'https://qyapi.weixin.qq.com/cgi-bin/material/get_count';
    const API_FOREVER_LIST = 'https://qyapi.weixin.qq.com/cgi-bin/material/batchget';
    const API_UPLOAD_IMG = 'https://qyapi.weixin.qq.com/cgi-bin/media/uploadimg';

    /**
     * 允许上传的类型.
     *
     * @var array
     */
    protected $allowTypes = array(
                             'image',
                             'voice',
                             'video',
                             'file',
                             'news',
                            );

    /**
     * Http对象
     *
     * @var Http
     */
    protected $http;

    /**
     * 是否上传永久素材.
     *
     * @var bool
     */
    protected $forever = false;

    protected $agentId = null;
    /**
     * constructor.
     *
     * @param string $appId
     * @param string $appSecret
     */
    public function __construct($appId, $appSecret)
    {
        $this->http = new Http(new AccessToken($appId, $appSecret));
    }

    /**
     * 是否为永久素材.
     *
     * @return Media
     */
    public function forever($agentId)
    {
        $this->forever = true;

        $this->agentId = $agentId;

        return $this;
    }

    /**
     * 上传媒体文件.
     *
     * @param string $type
     * @param string $path
     * @param array  $params
     *
     * @return string
     */
    protected function upload($type, $path, $params = array())
    {
        if (!file_exists($path) || !is_readable($path)) {
            throw new Exception("文件不存在或不可读 '$path'");
        }

        if (!in_array($type, $this->allowTypes, true)) {
            throw new Exception("错误的媒体类型 '{$type}'");
        }



        $queries = array('type' => $type, 'agentid' => $this->agentId);

        $options = array(
                    'files' => array('media' => $path),
                   );

        $url = $this->getUrl($type, $queries);

        $response = $this->http->post($url, $params, $options);

        $this->forever = false;

        if ($type == 'image') {
            return $response;
        }

        $response = Arr::only($response, array('media_id', 'thumb_media_id'));

        return array_pop($response);
    }

    /**
     * 新增图文素材.
     * @param @agentId 应用id
     * @param array $articles
     *
     * @return string
     */
    public function news($agentId, array $articles)
    {
        $params = array('agentid'=> 5, 'mpnews'=>array('articles'=>$articles));

        $response = $this->http->jsonPost(self::API_FOREVER_NEWS_UPLOAD, $params);

        return $response['media_id'];
    }

    /**
     * 修改图文消息.
     *
     * @param string $mediaId
     * @param array  $articles
     * @param int    $agentId
     *
     * @return bool
     */
    public function updateNews($mediaId, array $articles, $agentId = 0)
    {
        $params = array(
                   'media_id' => $mediaId,
                   'agentid' => $agentId,
                   'mpnews'=>array('articles'=>$articles),
                  );

        return $this->http->jsonPost(self::API_FOREVER_NEWS_UPDATE, $params);
    }

    /**
     * 删除永久素材.
     *
     * @param string $mediaId
     *
     * @return bool
     */
    public function delete($mediaId)
    {
        return $this->http->get(self::API_FOREVER_DELETE, array('media_id' => $mediaId, 'agentid' => $this->agentId));
    }

    /**
     * 图片素材总数.
     *
     * @param string $type
     * @param int 应用id
     *
     * @return array|int
     */
    public function stats($type = null, $agentId)
    {
        $response = $this->http->get(self::API_FOREVER_COUNT.'?agentid='.$agentId);

        $response = new Bag($response);

        return $type ? $response->get($type) : $response;
    }

    /**
     * 获取永久素材列表.
     *
     * example:
     *
     * {
     *   "total_count": TOTAL_COUNT,
     *   "item_count": ITEM_COUNT,
     *   "item": [{
     *             "media_id": MEDIA_ID,
     *             "name": NAME,
     *             "update_time": UPDATE_TIME
     *         },
     *         //可能会有多个素材
     *   ]
     * }
     *
     * @param string $type
     * @param int    $offset
     * @param int    $count
     *
     * @return array
     */
    public function lists($type, $offset = 0, $count = 20, $agentId)
    {
        $params = array(
                   'type' => $type,
                   'offset' => intval($offset),
                   'count' => min(20, $count),
                   'agentid' => $agentId,
                  );

        return $this->http->jsonPost(self::API_FOREVER_LIST, $params);
    }

    /**
     * 上传图文消息内的图片
     * @param  string $filename 图片路径
     * @return string
     */
    public function uploadImg( $filename)
    {
        $options = array(
                    'files' => array('media' => $filename),
                   );

        $response = $this->http->jsonPost(self::API_UPLOAD_IMG, $params = array(), $options);

        return $response['url'];
    }

    /**
     * 下载媒体文件.
     *
     * @param string $mediaId
     * @param string $filename
     *
     * @return mixed
     */
    public function download($mediaId, $filename = '')
    {
        $params = array('media_id' => $mediaId, 'agentid' => $this->agentId);

        $api = $this->forever ? self::API_FOREVER_GET : self::API_TEMPORARY_GET;

        $contents = $this->http->get($api, $params);

        $filename = $filename ? $filename : $mediaId;

        if (!is_array($contents)) {
            $ext = File::getStreamExt($contents);

            file_put_contents($filename.$ext, $contents);

            return $filename.$ext;
        } else {
            return $contents;
        }
    }

    /**
     * 魔术调用.
     *
     * <pre>
     * $media->image($path); // $media->upload('image', $path);
     * </pre>
     *
     * @param string $method
     * @param array  $args
     *
     * @return string
     */
    public function __call($method, $args)
    {
        $args = array(
                 $method,
                 array_shift($args),
                );

        return call_user_func_array(array(__CLASS__, 'upload'), $args);
    }

    /**
     * 获取API.
     *
     * @param string $type
     * @param array  $queries
     *
     * @return string
     */
    protected function getUrl($type, $queries = array())
    {
        if ($type === 'news') {
            $api = self::API_FOREVER_NEWS_UPLOAD;
        } else {
            $api = $this->forever ? self::API_FOREVER_UPLOAD : self::API_TEMPORARY_UPLOAD;
        }

        return $api.'?'.http_build_query($queries);
    }
}
