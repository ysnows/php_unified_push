<?php


namespace MingYuanYun\Push\Gateways;


use MingYuanYun\Push\AbstractMessage;
use MingYuanYun\Push\Exceptions\GatewayErrorException;
use MingYuanYun\Push\Traits\HasHttpRequest;
use xmpush\Builder;
use xmpush\Constants;
use xmpush\Sender;
use xmpush\Subscription;

class XiaomiGateway extends Gateway
{
    use HasHttpRequest;

    const PUSH_URL = 'https://api.xmpush.xiaomi.com/v3/message/regid';

    const OK_CODE = 0;

    const GATEWAY_NAME = 'xiaomi';

    protected $maxTokens = 1000;
    /**
     * @var Sender
     */
    private $sender;

    public function __construct(array $config)
    {
        parent::__construct($config);
        Constants::setPackage($this->config->get("appPkgName"));
        Constants::setSecret($this->config->get("appSecret"));

        $this->sender = new Sender();


    }

    public function requestAuthToken()
    {
        return null;
    }

    public function pushNotice($to, AbstractMessage $message, array $options = [])
    {


        $msg = new Builder();
        $msg->title($message->title);
        $msg->description($message->subTitle);
        $msg->passThrough(0);
        $msg->notifyType($message->notifyType);
        $msg->extra(Builder::soundUri, $message->soundUrl);
        $msg->payload(json_encode($message->payload)); // 对于预定义点击行为，payload会通过点击进入的界面的intent中的extra字段获取，而不会调用到onReceiveMessage方法。
        $msg->extra(Builder::notifyForeground, 1);
        $msg->restrictedPackageNames([$this->config->get("appPkgName")]);
        $msg->notifyId($message->notifyId);
        $msg->build();


        return $this->sender->sendToIds($msg, $to);
    }


    public function pushTopic($topic, AbstractMessage $message, array $options = [])
    {
        $msg = new Builder();
        $msg->title($message->title);
        $msg->description($message->subTitle);
        $msg->passThrough(0);
        $msg->notifyType(intval($message->notifyType));
        $msg->extra(Builder::soundUri, 'android.resource://' . Constants::$packageName . '/raw/' . trim($message->soundUrl));

        $msg->payload(json_encode($message->payload)); // 对于预定义点击行为，payload会通过点击进入的界面的intent中的extra字段获取，而不会调用到onReceiveMessage方法。
        $msg->extra(Builder::notifyForeground, 1);
        $msg->restrictedPackageNames([$this->config->get("appPkgName")]);
        $msg->notifyId($message->notifyId);
        $msg->build();

        return $this->sender->broadcast($msg, $topic);
    }

    public function addTopic($regid, $topic)
    {
        $subscription = new Subscription();
        return $subscription->subscribe($regid, $topic);
    }

    public function removeTopic($regid, $topic)
    {
        $subscription = new Subscription();
        return $subscription->unsubscribe($regid, $topic);
    }

    public function addUserAccount($regid, $userAccount)
    {
        return $this->addTopic($regid, $userAccount);
    }

    public function removeUserAccount($regid, $userAccount)
    {
        return $this->removeTopic($regid, $userAccount);
    }

    public function pushUserAccount($to, AbstractMessage $message, array $options = [])
    {
        return $this->pushTopic($to, $message, $options);
    }
}
