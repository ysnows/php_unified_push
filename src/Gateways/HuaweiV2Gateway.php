<?php

namespace MingYuanYun\Push\Gateways;


use MingYuanYun\Push\AbstractMessage;
use MingYuanYun\Push\Exceptions\GatewayErrorException;
use MingYuanYun\Push\Traits\HasHttpRequest;

class HuaweiV2Gateway extends Gateway
{
    use HasHttpRequest;

    // https://developer.huawei.com/consumer/cn/doc/development/HMS-References/push-sendapi

    const AUTH_URL = 'https://oauth-login.cloud.huawei.com/oauth2/v2/token';

    // https://push-api.cloud.huawei.com/v1/[appid]/messages:send
    const PUSH_URL = 'https://push-api.cloud.huawei.com/v1/%s/messages:send';
    const SUBSCRIBE_TOPIC_URL = 'https://push-api.cloud.huawei.com/v1/%s/topic:subscribe';
    const UNSUBSCRIBE_TOPIC_URL = 'https://push-api.cloud.huawei.com/v1/%s/topic:unsubscribe';

    const OK_CODE = '80000000';

    const GATEWAY_NAME = 'huawei-v2';

    protected $maxTokens = 1000;

    protected $headers = [
        'Content-Type' => 'application/x-www-form-urlencoded',
    ];

    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    public function pushNotice($to, AbstractMessage $message, array $options = [])
    {
        if (empty($this->getAuthToken())) {
            return "请先获取authToken";
        }

        $androidConfig = [
            'collapse_key' => -1,
            'bi_tag' => $message->businessId ?: '',
            'notification' => [
                'title' => $message->title,
                'body' => $message->subTitle,
                'tag' => $message->notifyId ?: null,
                'default_sound' => true,
                'importance' => 'NORMAL',
                'sound' => 'raw/order',
                'channel_id' => 'RingRing',
//                'notify_id' => $message->notifyId ?: -1,
                'click_action' => [
                    'type' => 1,
                    'intent' => $this->generateIntent($this->config->get('appPkgName'),
                        ['title' => $message->title,
                            'description' => $message->subTitle,
                            'payload' => json_encode($message->payload, JSON_UNESCAPED_UNICODE),
                        ]),
                ]
            ]
        ];
        if ($message->badge) {
            if (preg_match('/^\d+$/', $message->badge)) {
                $androidConfig['notification']['badge'] = [
                    'set_num' => intval($message->badge),
                    'class' => 'com.mysoft.core.activity.LauncherActivity'
                ];
            } else {
                $androidConfig['notification']['badge'] = [
                    'add_num' => intval($message->badge),
                    'class' => 'com.mysoft.core.activity.LauncherActivity'
                ];
            }
        }
        $androidConfig = $this->mergeGatewayOptions($androidConfig, $message->gatewayOptions);
        $data = [
            'message' => [
                'token' => $this->formatTo($to),
                'android' => $androidConfig,
            ],
        ];

        $this->setHeader('Authorization', 'Bearer ' . $this->getAuthToken());

        $result = $this->postJson($this->buildPushUrl(), $data, $this->getHeaders());
        if (!isset($result['code']) || $result['code'] != self::OK_CODE) {
            throw new GatewayErrorException(sprintf(
                '华为推送失败 > [%s] %s',
                isset($result['code']) ? $result['code'] : '-99',
                json_encode($result, JSON_UNESCAPED_UNICODE)
            ));
        }
        return $result['requestId'];
    }

    public function pushTopic($topic, AbstractMessage $message, array $options = [])
    {

        if (empty($this->getAuthToken())) {
            return "请先获取authToken";
        }

        $androidConfig = [
            'collapse_key' => -1,
            'bi_tag' => $message->businessId ?: '',
            'notification' => [
                'title' => $message->title,
                'body' => $message->subTitle,
                'tag' => $message->notifyId ?: null,
//                'notify_id' => $message->notifyId ?: -1,
                'click_action' => [
                    'type' => 1,
                    'intent' => $this->generateIntent($this->config->get('appPkgName'),
                        [
                            'title' => $message->title,
                            'description' => $message->subTitle,
                            'payload' => json_encode($message->payload, JSON_UNESCAPED_UNICODE),
                        ]),
                ]
            ]
        ];
        if ($message->badge) {
            if (preg_match('/^\d+$/', $message->badge)) {
                $androidConfig['notification']['badge'] = [
                    'set_num' => intval($message->badge),
                    'class' => 'com.mysoft.core.activity.LauncherActivity'
                ];
            } else {
                $androidConfig['notification']['badge'] = [
                    'add_num' => intval($message->badge),
                    'class' => 'com.mysoft.core.activity.LauncherActivity'
                ];
            }
        }
        $androidConfig = $this->mergeGatewayOptions($androidConfig, $message->gatewayOptions);
        $data = [
            'message' => [
                'topic' => $topic,
                'android' => $androidConfig,
            ],
        ];

        $this->setHeader('Authorization', 'Bearer ' . $this->getAuthToken());

        $result = $this->postJson($this->buildPushUrl(), $data, $this->getHeaders());
        if (!isset($result['code']) || $result['code'] != self::OK_CODE) {
            throw new GatewayErrorException(sprintf(
                '华为推送失败 > [%s] %s',
                isset($result['code']) ? $result['code'] : '-99',
                json_encode($result, JSON_UNESCAPED_UNICODE)
            ));
        }
        return $result['requestId'];
    }

    public function addTopic($regid, $topic)
    {

        if (empty($this->getAuthToken())) {
            return "请先获取authToken";
        }

        $data = [
            'message' => [
                'topic' => $topic,
                'tokenArray' => [$regid],
            ],
        ];

        $this->setHeader('Authorization', 'Bearer ' . $this->getAuthToken());

        $result = $this->postJson($this->buildSubscribeTopicUrl(), $data, $this->getHeaders());
        if (!isset($result['code']) || $result['code'] != self::OK_CODE) {
            throw new GatewayErrorException(sprintf(
                '华为推送失败 > [%s] %s',
                isset($result['code']) ? $result['code'] : '-99',
                json_encode($result, JSON_UNESCAPED_UNICODE)
            ));
        }
        return $result['requestId'];
    }

    public function removeTopic($regid, $topic)
    {

        if (empty($this->getAuthToken())) {
            return "请先获取authToken";
        }

        $data = [
            'message' => [
                'topic' => $topic,
                'tokenArray' => [$regid],
            ],
        ];

        $this->setHeader('Authorization', 'Bearer ' . $this->getAuthToken());

        $result = $this->postJson($this->buildUnsubscribeTopicUrl(), $data, $this->getHeaders());
        if (!isset($result['code']) || $result['code'] != self::OK_CODE) {
            throw new GatewayErrorException(sprintf(
                '华为推送失败 > [%s] %s',
                isset($result['code']) ? $result['code'] : '-99',
                json_encode($result, JSON_UNESCAPED_UNICODE)
            ));
        }
        return $result['requestId'];
    }

    public function requestAuthToken()
    {
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config->get('clientId'),
            'client_secret' => $this->config->get('clientSecret')
        ];
        $result = $this->post(self::AUTH_URL, $data, $this->getHeaders());

        if (!isset($result['access_token'])) {
            throw new GatewayErrorException(sprintf(
                '获取华为推送token失败 > [%s] %s',
                isset($result['error']) ? $result['error'] : '-99',
                isset($result['error_description']) ? $result['error_description'] : '未知异常'
            ));
        }

        return [
            'token' => $result['access_token'],
            'expires' => $result['expires_in']
        ];
    }

    protected function getTimestamp()
    {
        return strval(time());
    }

    protected function buildPushUrl()
    {
        return sprintf(self::PUSH_URL, $this->config->get('clientId'));
    }

    protected function buildSubscribeTopicUrl()
    {
        return sprintf(self::SUBSCRIBE_TOPIC_URL, $this->config->get('clientId'));
    }

    protected function buildUnsubscribeTopicUrl()
    {
        return sprintf(self::UNSUBSCRIBE_TOPIC_URL, $this->config->get('clientId'));
    }

    protected function formatTo($to)
    {
        if (!is_array($to)) {
            $to = [$to];
        } else {
            $this->checkMaxToken($to);
        }
        return $to;
    }

    public function addUserAccount($regid, $userAccount)
    {

    }

    public function removeUserAccount($regid, $userAccount)
    {

    }

    public function pushUserAccount($to, AbstractMessage $message, array $options = [])
    {

    }
}
