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
//        $msg->extra(Builder::notifyEffect, 1); // 此处设置预定义点击行为，1为打开app
        $msg->extra(Builder::notifyForeground, 1);
        $msg->restrictedPackageNames([$this->config->get("appPkgName")]);
        $msg->notifyId($message->notifyId);
        $msg->build();


        return $this->sender->sendToIds($msg, $to);
    }

    public function pushNoticeBac($to, AbstractMessage $message, array $options = [])
    {
        $this->setHeader('Authorization', sprintf('key=%s', $this->config->get('appSecret')));
        $data = [
            'payload' => json_encode($message->payload),
            'restricted_package_name' => $this->config->get('appPkgName'),
            'pass_through' => 0,
            'title' => $message->title,
            'notify_id' => $message->notifyId,
            'description' => $message->subTitle,
//            'extra.notify_effect' => '1',
            'extra.intent_uri' => $this->generateIntent($this->config->get('appPkgName'), $message->extra),
            'registration_id' => $this->formatTo($to),
        ];
        $message->notifyId && $data['extra.jobkey'] = $message->notifyId;

        if ($message->callback) {
            $data['extra.callback'] = $message->callback;
            if ($message->callbackParam) {
                $data['extra.callback.param'] = $message->callbackParam;
            }
        }
        $data = $this->mergeGatewayOptions($data, $message->gatewayOptions);

        $result = $this->post(self::PUSH_URL, $data, $this->getHeaders());
        $this->assertFailure($result, '小米推送失败');

        $returnData = $result['data'];
        return $returnData['id'];
    }

    protected function formatTo($to)
    {
        if (!is_array($to)) {
            $to = [$to];
        } else {
            $this->checkMaxToken($to);
        }
        return implode(',', $to);
    }

    protected function assertFailure($result, $message)
    {
        if (!isset($result['code']) || $result['code'] != self::OK_CODE) {
            throw new GatewayErrorException(sprintf(
                '%s > [%s] %s',
                $message,
                isset($result['code']) ? $result['code'] : '-99',
                json_encode($result, JSON_UNESCAPED_UNICODE)
            ));
        }
    }

    public function pushTopic($topic, AbstractMessage $message, array $options = [])
    {
        $msg = new Builder();
        $msg->title($message->title);
        $msg->description($message->subTitle);
        $msg->passThrough(0);
        $msg->payload(json_encode($message->payload)); // 对于预定义点击行为，payload会通过点击进入的界面的intent中的extra字段获取，而不会调用到onReceiveMessage方法。
//        $msg->extra(Builder::notifyEffect, 1); // 此处设置预定义点击行为，1为打开app
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
}
