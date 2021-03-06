<?php


namespace MingYuanYun\Push\Contracts;


use MingYuanYun\Push\AbstractMessage;


interface GatewayInterface
{
    public function getName();


    public function addTopic($regid, $topic);

    public function removeTopic($regid, $topic);

    public function pushTopic($to, AbstractMessage $message, array $options = []);


    public function addUserAccount($regid, $userAccount);

    public function removeUserAccount($regid, $userAccount);

    public function pushUserAccount($to, AbstractMessage $message, array $options = []);


    public function getGatewayName();

    public function requestAuthToken();

    public function setAuthToken($token);

    public function getAuthToken();


    public function pushNotice($to, AbstractMessage $message, array $options = []);

}
