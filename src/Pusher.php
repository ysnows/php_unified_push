<?php

namespace MingYuanYun\Push;

use MingYuanYun\Push\Exceptions\Exception;
use MingYuanYun\Push\Gateways\HuaweiV2Gateway;
use MingYuanYun\Push\Gateways\XiaomiGateway;
use think\facade\Config;

class Pusher
{

    private $config = [];

    public function __construct()
    {
        $this->config = Config::pull('push')['gateway'];
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

    public function addUserAccount($userAccount, $platform, $token)
    {
        $push = new Push($this->config);
        $push->setPusher($platform);
        return $push->addUserAccount($token, $userAccount);
    }

    public function removeUserAccount($userAccount, $platform, $token)
    {
        $push = new Push($this->config);
        $push->setPusher($platform);
        return $push->removeUserAccount($token, $userAccount);

    }


    public function pushToTopic($topic,  $message)
    {
        if (is_string($message['payload'])) {
            $message['payload'] = json_decode($message['payload'], true);
        }

        $message['businessId'] = uniqid();
        $message['notifyId'] = $this->createNotifyid();

        $gateway_name_arr = array(XiaomiGateway::GATEWAY_NAME, HuaweiV2Gateway::GATEWAY_NAME);


        $res = array();
        foreach ($gateway_name_arr as $item) {
            $push = new Push($this->config);
            $push->setPusher($item);
            $result = $push->pushTopicNotice($topic, $message);
            $res[] = $result;
        }


        return $res;
    }

    public function pushToUserAccount($userAccount, AbstractMessage $message)
    {
        if (is_string($message->payload)) {
            $message->payload = json_decode($message->payload, true);
        }

        $message->businessId = uniqid();
        $message->notifyId = $this->createNotifyid();

        $gateway_name_arr = array(XiaomiGateway::GATEWAY_NAME, HuaweiV2Gateway::GATEWAY_NAME);


        $res = array();
        foreach ($gateway_name_arr as $item) {
            $push = new Push($this->config);
            $push->setPusher($item);
            $result = $push->pushUserAccount($userAccount, $message);
            $res[] = $result;
        }


        return $res;
    }


    public function pushNotice(AbstractMessage $message, $platform, $token = [])
    {
        if (is_string($message->payload)) {
            $message->payload = json_decode($message->payload, true);
        }
        $message->businessId = uniqid();
        $message->notifyId = $this->createNotifyid();

        $push = new Push($this->config);
        $push->setPusher($platform);

        try {
            return $push->pushNotice($token, $message, []);
        } catch (Exception $e) {
            return $e;
        }
    }

    private function createNotifyid()
    {
        $redis = new Redis();
        $notify_id = $redis->get('notify_id', 0);
        $redis->inc('notify_id', 1);
        if ($notify_id > 100) {
            $redis->set('notify_id', 0);
        }
        return $notify_id;
    }

}
