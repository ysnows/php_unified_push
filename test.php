<?php

use MingYuanYun\Push\Notice;

require __DIR__ . '/vendor/autoload.php';


$notice = new Notice();
//$res = $notice->addTopic('topic_mi', 'xiaomi', '0S24GxgOtrU7QZ29nPlg18uEfgeiT33ZVW88XWj78PjwtAibK6P/NBSOcZFVBgIE');
$res = $notice->pushToTopic('topic_mi', "hello1", "world", "");

echo json_encode($res, JSON_UNESCAPED_UNICODE);
