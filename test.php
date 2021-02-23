<?php

use MingYuanYun\Push\Notice;

require __DIR__ . '/vendor/autoload.php';


$notice = new Notice();
$regid = "0S24GxgOtrU7QZ29nPlg18uEfgeiT33ZVW88XWj78PjwtAibK6P/NBSOcZFVBgIE";
//$res = $notice->addTopic('topic_XBTC_10052', 'xiaomi', $regid);
//$res = $notice->removeTopic('topic_XBTC_10052', 'xiaomi', '0S24GxgOtrU7QZ29nPlg18uEfgeiT33ZVW88XWj78PjwtAibK6P/NBSOcZFVBgIE');
$res = $notice->pushToTopic('topic_XBTC_10052', "hello1", "world", "");
//$res = $notice->getTopicOfRegid($regid);
echo json_encode($res->getRaw());
