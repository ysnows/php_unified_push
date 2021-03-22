<?php

namespace MingYuanYun\Push;

use app\common\driver\Redis;
use MingYuanYun\Push\Exceptions\Exception;
use MingYuanYun\Push\Push;
use think\Controller;
use think\facade\Cache;
use xmpush\Builder;
use xmpush\Constants;
use xmpush\DevTools;
use xmpush\Sender;
use xmpush\Subscription;
use xmpush\TargetedMessage;

class Notice
{

    private $config = [
        'huawei' => [
            'appPkgName' => '', // 包名
            'clientId' => '',
            'clientSecret' => ''
        ],
        'huawei-v2' => [
            'appPkgName' => 'com.mixpush.huawei',
            'clientId' => '103816707',
            'clientSecret' => 'c79b8c8873cb3b9b5dc38d82cc4980d228e6a883d694662f72a6d53fe2195674'
        ],
        'meizu' => [
            'appPkgName' => 'com.mixpush.meizu',
            'appId' => '138461',
            'appSecret' => '3b904f450ca84b2092b9b1648fa24f0d'
        ],
        'xiaomi' => [
            'appPkgName' => 'com.quansu.trailertiger',
            'appSecret' => 'iAg1xJdHTKUlFNsvJaflaA=='
        ],
        'oppo' => [
            'appPkgName' => 'com.mixpush.oppo',
            'appKey' => '5ab6095516994c49b6878afbc7e734ed',
            'masterSecret' => '048d6085dd6f49f489eb3233d839ad9b'
        ],
        'vivo' => [
            'appPkgName' => 'com.quansu.trailertiger',
            'appId' => '105462756',
            'appKey' => '2f8871fe668823f9b11612d6e6e02003',
            'appSecret' => 'fb2dc732-56eb-4378-96a9-0f8f0cebdf02'
        ]
    ];

    public function mitest($regid)
    {
        $secret = 'iAg1xJdHTKUlFNsvJaflaA==';
        $package = 'com.quansu.trailertiger';

// 常量设置必须在new Sender()方法之前调用
        Constants::setPackage($package);
        Constants::setSecret($secret);

        $aliasList = array('alias1', 'alias2');
        $title = '你好';
        $desc = '这是一条mipush推送消息';
        $payload = '{"test":1,"ok":"It\'s a string"}';

        $sender = new Sender();
// $sender->setRegion(Region::China);// 支持海外

// message1 演示自定义的点击行为
        $message1 = new Builder();
        $message1->title($title);  // 通知栏的title
        $message1->description($desc); // 通知栏的descption
        $message1->passThrough(0);  // 这是一条通知栏消息，如果需要透传，把这个参数设置成1,同时去掉title和descption两个参数
        $message1->extra(Builder::soundUri, "android.resource://com.quansu.trailertiger/2131755008");
        $message1->notifyType(7);  // 这是一条通知栏消息，如果需要透传，把这个参数设置成1,同时去掉title和descption两个参数
        $message1->payload($payload); // 携带的数据，点击后将会通过客户端的receiver中的onReceiveMessage方法传入。
        $message1->extra(Builder::notifyForeground, 1); // 应用在前台是否展示通知，如果不希望应用在前台时候弹出通知，则设置这个参数为0
        $message1->notifyId(25); // 通知类型。最多支持0-4 5个取值范围，同样的类型的通知会互相覆盖，不同类型可以在通知栏并存

        $message1->build();
//        $targetMessage = new TargetedMessage();
//        $targetMessage->setTarget('alias1', TargetedMessage::TARGET_TYPE_ALIAS); // 设置发送目标。可通过regID,alias和topic三种方式发送
//        $targetMessage->setMessage($message1);

// message2 演示预定义点击行为中的点击直接打开app行为
//        $message2 = new Builder();
//        $message2->title($title);
//        $message2->description($desc);
//        $message2->passThrough(0);
//        $message2->payload($payload); // 对于预定义点击行为，payload会通过点击进入的界面的intent中的extra字段获取，而不会调用到onReceiveMessage方法。
//        $message2->extra(Builder::notifyEffect, 1); // 此处设置预定义点击行为，1为打开app
//        $message2->extra(Builder::notifyForeground, 1);
//        $message2->notifyId(0);
//        $message2->build();
//        $targetMessage2 = new TargetedMessage();
//        $targetMessage2->setTarget('alias2', TargetedMessage::TARGET_TYPE_ALIAS);
//        $targetMessage2->setMessage($message2);

//        $targetMessageList = array($targetMessage, $targetMessage2);
//print_r($sender->multiSend($targetMessageList,TargetedMessage::TARGET_TYPE_ALIAS)->getRaw());


        $result = $sender->sendToIds($message1, [$regid]);
        return $result;
//        print_r($sender->sendToAliases($message1, $aliasList)->getRaw());
//$stats = new Stats();
//$startDate = '20140301';
//$endDate = '20140312';
//print_r($stats->getStats($startDate,$endDate)->getData());
//$tracer = new Tracer();
//print_r($tracer->getMessageStatusById('t1000270409640393266xW')->getRaw());    }

    }

    public function setAlias($alias, $platform, $token)
    {
        $redis = new Redis();
        $res = $redis->tag('alias')->set($alias, [$platform => $token]);

        return outJson($res);
    }

    public function getAlias($alias)
    {
        $redis = new Redis();
        $aliasValue = $redis->get($alias);
        return outJson($aliasValue);
    }

    public function addTopic($topic, $platform, $token)
    {
        $push = new Push($this->config);
        $push->setPusher($platform);
        return $push->addTopic($token, $topic);
    }

    public function removeTopic($topic, $platform, $token)
    {
        $push = new Push($this->config);
        $push->setPusher($platform);
        return $push->removeTopic($token, $topic);

    }

    public function getTopic($topic = null)
    {
        $redis = new Redis();
        if (empty($topic)) {
            $all = $redis->handler()->keys('topic_*');
            return outJson($all);
        }

        $platform_token_arr = $redis->handler()->sMembers($topic);

        $topic_arr = array();
        foreach ($platform_token_arr as $item) {
            $explode = explode('_', $item, 2);
            $topic_arr[$explode[0]]['data'][] = $explode[1];
        }
//        $res = $redis->getTagItem($topic);
        return outJson($topic_arr, 1, 'success', count($platform_token_arr));
    }

    public function pushToTopic($topic, $title, $subTitle, $payload)
    {
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        $platform_token_arr = ['xiaomi_0S24GxgOtrU7QZ29nPlg18uEfgeiT33ZVW88XWj78PjwtAibK6P/NBSOcZFVBgIE'];

        $topic_arr = array();
        foreach ($platform_token_arr as $item) {
            $explode = explode('_', $item, 2);
            $topic_arr[$explode[0]][] = $explode[1];
        }


        $message = [
            'businessId' => uniqid(),
            'title' => $title,
            'subTitle' => $subTitle,
            'payload' => $payload,
            'notifyId' => $this->createNotifyid(),
            'extra' => [
                'key' => 'value',
            ],

            'gatewayOptions' => [
                'xiaomi' => [
                    'extra.notify_foreground' => '1',
                ],
                'huawei' => [
                    'hps' => [
                        'ext' => [
                            'badgeAddNum' => '1',
                            'badgeClass' => 'com.mysoft.core.activity.LauncherActivity',
                        ]
                    ]
                ]
            ],
        ];

        $res = array();
        foreach ($topic_arr as $platform => $tokens) {
            $item = array();

            $item['platform'] = $platform;
            $item['tokens'] = $tokens;
            if ($platform == "xiaomi") {

                $push = new Push($this->config);
                $push->setPusher($platform, null);
                $result = $push->pushTopicNotice($topic, $message);

            } elseif ($platform == "huawei-v2") {

                $push = new Push($this->config);
                $authToken = $push->requestAuthToken();
                $push->setPusher($platform, $authToken);
                $result = $push->pushTopicNotice($topic, $message);

            } else {
                $result = $this->pushNotice($message, $platform, $tokens);
            }
            $item['result'] = $result;

            $res[] = $item;
        }
        return $res;
    }


    public function pushNotice($message, $platform, $token = [])
    {

        $push = new Push($this->config);
        $push->setPusher($platform);

        $authToken = $push->requestAuthToken();
        $options = array();
        $options['token'] = $authToken;

        try {
            return $push->pushNotice($token, $message, $options);
        } catch (Exception $e) {
            return $e;
        }
    }


    public function getTopicOfRegid($regid)
    {
        $platform = 'xiaomi';
        $item['platform'] = $platform;
        $push = new Push($this->config);
        $push->setPusher($platform);

        $devTools = new DevTools();
        $res = $devTools->getTopicsOf("com.quansu.trailertiger", $regid);
        return $res;

    }

    private function createNotifyid()
    {
//        $notify_id = Cache::get('notify_id', 0);
//        Cache::inc('notify_id', 1);
//        if ($notify_id > 100) {
//            Cache::set('notify_id', 0);
//        }
//        return $notify_id;
        return 1;
    }

}
