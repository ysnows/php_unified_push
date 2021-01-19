<?php


namespace MingYuanYun\Push\Gateways;


use MingYuanYun\Push\AbstractMessage;
use MingYuanYun\Push\Exceptions\GatewayErrorException;
use MingYuanYun\Push\Traits\HasHttpRequest;

class XiaomiGateway extends Gateway
{
    use HasHttpRequest;

    const PUSH_URL = 'https://api.xmpush.xiaomi.com/v3/message/regid';

    const OK_CODE = 0;

    const GATEWAY_NAME = 'xiaomi';

    protected $maxTokens = 100;


    public function getAuthToken()
    {
        return null;
    }

    public function pushNotice($to, AbstractMessage $message, array $options = [])
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
}
