<?php
namespace Jpay\PaymentSDK;
require_once "common.php";
require_once "JpayPaymentSDK.php";

$mid = 10130;
$secret = 'hdt1yur0rcxbu6g7wlkyw3e55shfr2fr';
$jpay = new JpayPaymentSDK($mid, $secret,"cashapp");
$res = $jpay->notify();
writeLogs("notify", "notify result :".var_export($res,true));
echo "success";
