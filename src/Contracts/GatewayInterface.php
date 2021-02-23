<?php


namespace MingYuanYun\Push\Contracts;


use MingYuanYun\Push\AbstractMessage;


interface GatewayInterface
{
    public function getName();

    public function addTopic($regid, $topic);
    public function removeTopic($regid, $topic);

    public function getGatewayName();

    public function getAuthToken();

    public function pushNotice($to, AbstractMessage $message, array $options = []);

    public function pushTopic($to, AbstractMessage $message, array $options = []);
}
