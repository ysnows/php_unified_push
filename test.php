<?php

use MingYuanYun\Push\Pusher;

require __DIR__ . '/vendor/autoload.php';


$notice = new Pusher();
$regid = "v9HhxS0Sp/AhjZDSEMn40k6Jo5uUmH6iK7wzbIB8ZGqiUMevm4zeye9OB24ondXK";
$res = $notice->addTopic('topic_XBTC_10052', '', $regid);
//$res = $notice->removeTopic('topic_XBTC_10052', 'xiaomi', '0S24GxgOtrU7QZ29nPlg18uEfgeiT33ZVW88XWj78PjwtAibK6P/NBSOcZFVBgIE');
//$res = $notice->pushToTopic('topic_XBTC_10052', "hello1", "world", "");
//$res = $notice->getTopicOfRegid($regid);
//echo json_encode($res->getRaw());
//$notice->mitest($regid);

