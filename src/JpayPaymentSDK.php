<?php
namespace Jpay\PaymentSDK;
require_once "common.php";
//Test version, mid will be set as default
class JpayPaymentSDK
{

    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_FAIL = 'FAIL';
    const STATUS_PENDING = 'PENDING';

    const CODE_SUCCESS = 0;
    const CODE_FAIL = -1;

    const CODE_BAD_REQUEST = 400;
    const CODE_UNAUTHORIZED = 401;
    const CODE_FORBIDDEN = 403;
    const CODE_NOT_FOUND = 404;
    const CODE_SERVER_ERROR = 500;

    const CASH_APP = "cashapp";
    const APM = "apm";
    const VIRTUAL = "virtual";

    const ENTITY = "entity";

    const PAYOUT = "payout";




    public array $dev_info = [
        self::CASH_APP => ['mid'=>10130, 'apikey'=> 'hdt1yur0rcxbu6g7wlkyw3e55shfr2fr'],
        self::APM => ['mid'=>10172, 'apikey'=> 'prx5d81g2ytb1mmokl9mohjsmsb09do3'],
        self::VIRTUAL=>['mid'=>10153, 'apikey'=> '9k8rx7j0fxa7deb250huvghfbbjnu23h'],
        self::ENTITY=>['mid'=>10151, 'apikey'=> '3p17o32a83gge5tho3vpv0m61nvhtye9'],
    ];

    const DEV = 1;

    const PRO = 0;

    //test enum
    protected array $config = [
        'dev_gateway_url' => "https://sandbox.j-pay.net/pay_index",
        'gateway_url' => "https://api.j-pay.net/pay_index",

        'dev_subscribe_cancel_url' => "https://sandbox.j-pay.net/pay/trade/subscribeCancel",
        'subscribe_cancel_url' => "https://api.j-pay.net/pay/trade/subscribeCancel",

        'dev_query_order_url' => "https://sandbox.j-pay.net/pay/trade/query",
        'query_order_url' => "https://api.j-pay.net/pay/trade/query",

        'dev_refund_url' => "https://sandbox.j-pay.net/pay/trade/refund",
        'refund_url' => "https://api.j-pay.net/pay/trade/refund",

        'dev_query_refund_url' => "https://sandbox.j-pay.net/pay/trade/refundQuery",
        'query_refund_url' => "https://api.j-pay.net/pay/trade/queryRefund",

        'dev_payout_url' => "https://sandbox.j-pay.net/api/v1/payment/createOrder",
        'payout_url'=> "https://api.j-pay.net/api/v1/payment/createOrder",

        'dev_query_payout_url' => "https://sandbox.j-pay.net/api/v1/payment/getOrder",
        'query_payout_url' => "https://api.j-pay.net/api/v1/payment/getOrder",

    ];

    public function __construct(string $merchant_id, string $secret_key, string $ptype, bool $isDebug = true)
    {
        $this->config['mid'] = $merchant_id;
        $this->config['skey'] = $secret_key;
        $this->config['ptype'] = $ptype;
        $this->config['env'] = $isDebug?self::DEV : self::PRO;

        $this->apmRequireKey = ['pay_orderid', 'pay_notifyurl', 'pay_callbackurl', 'pay_amount', 'pay_currency', 'pay_firstname', 'pay_lastname', 'pay_email_address', 'pay_telephone', 'pay_country_iso_code_2'];
        $this->entityRequireKey = ['pay_orderid', 'pay_notifyurl', 'pay_callbackurl', 'pay_amount', 'pay_currency', 'pay_firstname', 'pay_lastname', 'pay_email_address', 'pay_telephone', 'pay_country_iso_code_2',
            'pay_productname', 'pay_street_address1', 'pay_street_address2', 'pay_city', 'pay_postcode', 'pay_state', 'shipping_street_address1', 'shipping_street_address2', 'shipping_city', 'shipping_state', 'shipping_postcode',
            'shipping_country_iso_code_2', 'shipping_telephone'];
        $this->virtualRequireKey = [
            'pay_orderid', 'pay_notifyurl', 'pay_callbackurl', 'pay_amount', 'pay_currency', 'pay_firstname', 'pay_lastname', 'pay_email_address', 'pay_telephone', 'pay_country_iso_code_2','pay_productname'
        ];

        $this->subRequireKey = [
            'pay_orderid', 'pay_notifyurl', 'pay_callbackurl', 'pay_amount', 'pay_currency', 'pay_firstname', 'pay_lastname', 'pay_email_address', 'pay_telephone', 'pay_country_iso_code_2','pay_productname',
            'pay_cardno', 'pay_cardmonth', 'pay_cardyear', 'pay_cardcvv'
        ];

        $this->refundRequireKey = [
            'pay_orderid', 'transaction_id', 'currency', 'pay_amount', 'refund_amount'
        ];

        $this->payoutRequireKey = ['out_trade_no', 'amount', 'currency', 'orderType', 'notifyurl', 'remark', 'firstname', 'payeeaccount'];


    }
    /**
     * @param $paymentData
     * @return void
     */

    public function payment($paymentData):array
    {
        $ptype = $this->config['ptype'];
        if ($ptype == self::CASH_APP or $ptype == self::APM){
            $check_res = checkRequiredFieldsWithMissing($paymentData, $this->apmRequireKey);
        }elseif ($ptype == self::VIRTUAL){
            $check_res = checkRequiredFieldsWithMissing($paymentData, $this->virtualRequireKey);
        }elseif ($ptype == self::ENTITY){
            $check_res = checkRequiredFieldsWithMissing($paymentData, $this->entityRequireKey);
        }else{
            return error_message(self::CODE_FAIL,self::STATUS_FAIL, "ptype parameters is error");
        }

        if ($check_res !==true){
            return error_message(self::CODE_FAIL,self::STATUS_FAIL, "Missing mandatory parameters", $check_res);
        }
        $gate_url = $this->config['dev_gateway_url'];
        $is_debug = $this->config['env'];
        $mid = $this->dev_info[$ptype]['mid'];
        $secret_key = $this->dev_info[$ptype]['apikey'];
        if (!$is_debug){
            $gate_url = $this->config['gateway_url'];
            $mid = $this->config['mid'];
            $secret_key = $this->config['skey'];
        }
        $payload = [
            'pay_memberid' => $mid,
            'pay_orderid' => $paymentData['pay_orderid'],
            'pay_applydate' => $paymentData['pay_applydate']?? date('Y-m-d H:i:s', time()),
            'pay_bankcode' => '901',
            'pay_notifyurl' => $paymentData['pay_notifyurl'],
            'pay_callbackurl' => $paymentData['pay_callbackurl'],
            'pay_amount' => $paymentData['pay_amount'],
            'pay_currency' => $paymentData['pay_currency'],
            'pay_url' => getDomainInfo(),
            'pay_firstname'=> $paymentData['pay_firstname'],
            'pay_lastname' => $paymentData['pay_lastname'],
            'pay_country_iso_code_2' => $paymentData['pay_country_iso_code_2'],
            'pay_email_address' => $paymentData['pay_email_address'],
            'pay_telephone' => $paymentData['pay_telephone'],
            'pay_language' => $paymentData['pay_language'] ?? 'en',
            'system' => $paymentData['system'] ?? 'other',
            'pay_ip' => getClientIp(),
            'pay_useragent' => getUserAgent()
        ];
        if ($ptype == self::APM){
            $payload['pay_type'] = 'apm';
        }
        if ($ptype == self::ENTITY){
            $payload['pay_street_address1'] = $paymentData['pay_street_address1'];
            $payload['pay_street_address2'] = $paymentData['pay_street_address2'];
            $payload['pay_city'] = $paymentData['pay_city'];
            $payload['pay_postcode'] = $paymentData['pay_postcode'];
            $payload['pay_state'] = $paymentData['pay_state'];
            $payload['shipping_firstname'] = $paymentData['shipping_firstname'];
            $payload['shipping_lastname'] = $paymentData['shipping_lastname'];
            $payload['shipping_street_address1'] = $paymentData['shipping_street_address1'];
            $payload['shipping_street_address2'] = $paymentData['shipping_street_address2'];
            $payload['shipping_city'] = $paymentData['shipping_city'];
            $payload['shipping_state'] = $paymentData['shipping_state'];
            $payload['shipping_postcode'] = $paymentData['shipping_postcode'];
            $payload['shipping_country_iso_code_2'] = $paymentData['shipping_country_iso_code_2'];
            $payload['shipping_telephone'] = $paymentData['shipping_telephone'];
        }
        if ($paymentData['pay_productname'] and !empty($paymentData['pay_productname'])){
            $payload['pay_productname'] = $paymentData['pay_productname'];
        }
        if ($paymentData['pay_postcode'] and !empty($paymentData['pay_postcode'])){
            $payload['pay_postcode'] = $paymentData['pay_postcode'];
        }
        if ($paymentData['pay_state'] and !empty($paymentData['pay_state'])){
            $payload['pay_state'] = $paymentData['pay_state'];
        }
        if ($paymentData['pay_method'] and !empty($paymentData['pay_method'])){
            $payload['pay_method'] = $paymentData['pay_method'];
        }
        return $this->execute_req($payload, $mid, $secret_key, $gate_url);

    }

    /**
     * @param array $paymentData
     * @return array
     */
    public function subscriptions(array $paymentData){
        $ptype = $this->config['ptype'];
        $check_res = checkRequiredFieldsWithMissing($paymentData, $this->subRequireKey);
        if ($check_res !==true){
            return error_message(self::CODE_FAIL,self::STATUS_FAIL, "Missing mandatory parameters", $check_res);
        }
        //define test
        $mid = '10010';
        $secret_key = '7e4nicn14nhyup146dfbi8hpnpus9juz';
        $gate_url = $this->config['dev_gateway_url'];
        $is_debug = $this->config['env'];
        if (!$is_debug){
            $mid = $this->config['mid'];
            $secret_key = $this->config['skey'];
            $gate_url = $this->config['gateway_url'];
        }

        $payload = [
            'pay_memberid' => $mid,
            'pay_orderid' => $paymentData['pay_orderid'],
            'pay_applydate' => $paymentData['pay_applydate']?? date('Y-m-d H:i:s', time()),
            'pay_bankcode' => '901',
            'pay_notifyurl' => $paymentData['pay_notifyurl'],
            'pay_callbackurl' => $paymentData['pay_callbackurl'],
            'pay_amount' => $paymentData['pay_amount'],
            'pay_currency' => $paymentData['pay_currency'],
            'pay_url' => getDomainInfo(),
            'pay_firstname'=> $paymentData['pay_firstname'],
            'pay_lastname' => $paymentData['pay_lastname'],
            'pay_country_iso_code_2' => $paymentData['pay_country_iso_code_2'],
            'pay_email_address' => $paymentData['pay_email_address'],
            'pay_telephone' => $paymentData['pay_telephone'],
            'pay_language' => $paymentData['pay_language'] ?? 'en',
            'system' => $paymentData['system'] ?? 'other',
            'pay_cardmonth' => $paymentData['pay_cardmonth'],
            'pay_cardno' => $paymentData['pay_cardno'],
            'pay_cardyear' => $paymentData['pay_cardyear'],
            'pay_cardcvv' => $paymentData['pay_cardcvv'],
            'pay_ip' => getClientIp(),
            'pay_useragent' => getUserAgent()
        ];

        if ($paymentData['subscription_plan'] and !empty($paymentData['subscription_plan'])){
            $payload['subscription_plan'] = $paymentData['subscription_plan'];
        }

        return $this->execute_req($payload, $mid, $secret_key, $gate_url);
    }

    function subscription_cancel(array $inputdata){
        //define test
        $ptype = $this->config['ptype'];
        $mid = '10010';
        $secret_key = '7e4nicn14nhyup146dfbi8hpnpus9juz';
        $gate_url = $this->config['dev_subscribe_cancel_url'];
        $is_debug = $this->config['env'];
        if (!$is_debug){
            $mid = $this->config['mid'];
            $secret_key = $this->config['skey'];
            $gate_url = $this->config['subscribe_cancel_url'];
        }

        $orderid = $inputdata['pay_orderid'];
        $payload = [
            'pay_memberid' => $mid,
            'pay_orderid' => $inputdata['pay_orderid']
        ];
        return $this->execute_req($payload, $mid, $secret_key, $gate_url);
    }

    function query_order(array $inputdata){
        $ptype = $this->config['ptype'];
        $orderid = $inputdata['pay_orderid'];
        if (!$orderid){
            return error_message(self::CODE_FAIL,self::STATUS_FAIL, "Order id can't be empty");
        }
        $is_debug = $this->config['env'];
        $gate_url = $this->config['dev_query_order_url'];
        $mid = $this->config['mid'];
        $secret_key = $this->config['skey'];
        if (!$is_debug){
            $gate_url = $this->config['query_order_url'];
        }
        $payload = [
            'pay_memberid' => $mid,
            'pay_orderid' => $orderid
        ];
        return $this->execute_req($payload, $mid, $secret_key, $gate_url, false);

    }


    function refund(array $inputdata){
        $ptype = "refund";
        $orderid = $inputdata['pay_orderid'];
        $is_debug = $this->config['env'];
        $gate_url = $this->config['dev_refund_url'];
        $mid = $this->config['mid'];
        $secret_key = $this->config['skey'];
        if (!$is_debug){
            $gate_url = $this->config['refund_url'];
        }
        $check_res = checkRequiredFieldsWithMissing($inputdata, $this->refundRequireKey);
        if ($check_res !== true){
            return error_message(self::CODE_FAIL,self::STATUS_FAIL, "Missing mandatory parameters", $check_res);
        }
        $payload = [
            'pay_memberid' => $mid,
            'pay_orderid' => $orderid,
            'transaction_id' => $inputdata['transaction_id'],
        ];
        $sign_info = sign($payload, $mid, $secret_key, false);
        $inputdata['pay_memberid'] = $mid;
        $inputdata['pay_md5sign'] = $sign_info;
        $header = getHeader();
        $response = vpost($gate_url, $header, $inputdata);
        if (!is_array($response)) {
            $response = json_decode($response, true);
        }

        return $response;

    }

    function query_refund(array $inputdata){
        $ptype = $this->config['ptype'];
        $orderid = $inputdata['pay_orderid'];
        $is_debug = $this->config['env'];
        $gate_url = $this->config['dev_query_refund_url'];
        $mid = $this->config['mid'];
        $secret_key = $this->config['skey'];
        if (!$is_debug){
            $gate_url = $this->config['query_refund_url'];
        }
        if (!$orderid){
            return error_message(self::CODE_FAIL,self::STATUS_FAIL, "Order id can't be empty");
        }

        $payload = [
            'pay_memberid' => $mid,
            'pay_orderid' => $orderid
        ];
//        if (!empty($inputdata['transaction_id'])){
//            $payload['transaction_id'] = $inputdata['transaction_id'];
//        }
//        if (!empty($inputdata['refund_orderid'])){
//            $payload['refund_orderid'] = $inputdata['refund_orderid'];
//        }

        return $this->execute_req($payload, $mid, $secret_key, $gate_url,false);
    }


    /**
     * @param array $payload
     * @param mixed $mid
     * @param mixed $secret_key
     * @param mixed $gate_url
     * @return array
     */
    public function execute_req(array $payload, string $mid, string $secret_key, string $gate_url, bool $isDefind = true): array
    {
        $ptype = $this->config['ptype'];
        $sign_info = sign($payload, $mid, $secret_key, $isDefind);
        $payload['pay_md5sign'] = $sign_info;
        $header = getHeader();
        $response = vpost($gate_url, $header, $payload);
        if (!is_array($response)) {
            $response = json_decode($response, true);
        }
        return $response;
    }


    public function callback(){
        $input_data = file_get_contents('php://input');
        if (empty($input_data)){
            $input_data = $_GET;
        }
        !is_array($input_data) && $input_data = json_decode($input_data, true);
        return $input_data;
    }

    public function notify(){
        $input_data = file_get_contents('php://input');
        if (empty($input_data)){
            $input_data = $_POST;
        }
        parse_str($input_data, $data);
        $sign = $data['sign'];
        unset($data['sign']);
        unset($data['attach']);
        ksort($data);
        $mid = $this->config['mid'];
        $secret_key = $this->config['skey'];
        $my_sign = sign($data, $mid, $secret_key, false);
        if ($my_sign != $sign){
            return error_message(self::CODE_FAIL,self::STATUS_FAIL, "Invalid sign");
        }
        return $data;
    }

    public function refundNotify(){
        $input_data = file_get_contents('php://input');
        if (empty($input_data)){
            $input_data = $_POST;
        }
        $data = filter_input_array($input_data);
        $sign = $data['sign'];
        unset($data['sign']);
        unset($data['attach']);
        ksort($data);
        $mid = $this->config['mid'];
        $secret_key = $this->config['skey'];
        $my_sign = sign($data, $mid, $secret_key, false);
        if ($my_sign != $sign){
            return error_message(self::CODE_FAIL,self::STATUS_FAIL, "Invalid sign");
        }
        return $data;
    }

    public function payout(array $paymentData){
        $check_res = checkRequiredFieldsWithMissing($paymentData, $this->payoutRequireKey);
        if ($check_res !==true){
            return error_message(self::CODE_FAIL,self::STATUS_FAIL, "Missing mandatory parameters", $check_res);
        }
        $mid = '10010';
        $secret_key = '7e4nicn14nhyup146dfbi8hpnpus9juz';
        $orderid = $paymentData['out_trade_no'];
        $is_debug = $this->config['env'];
        $gate_url = $this->config['dev_payout_url'];
        if (!$is_debug){
            $gate_url = $this->config['payout_url'];
            $mid = $this->config['mid'];
            $secret_key = $this->config['skey'];
        }
        $orderType = $paymentData['orderType'];
        $payload = [
            'memberid' => $mid,
            'out_trade_no' => $paymentData['out_trade_no'],
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'],
            'orderType' => $orderType,
            'notifyurl' => $paymentData['notifyurl'],
            'remark' => $paymentData['remark'],
            'firstname'=> $paymentData['firstname'],
            'payeeaccount' => $paymentData['payeeaccount'],
            'orderid' => $orderid
        ];
        if (in_array($orderType, ['ach', 'Visa Direct', 'BANK']) )
        {
            if (empty($paymentData['lastname'])){
                return error_message(self::CODE_FAIL,self::STATUS_FAIL, "Last name can't be empty");
            }
            $payload['lastname'] = $paymentData['lastname'];
        }

        if (in_array($orderType, ['ach', 'Venmo', 'BANK']) )
        {
            if (empty($paymentData['payeecontact'])){
                return error_message(self::CODE_FAIL,self::STATUS_FAIL, "payeecontact can't be empty");
            }
            $payload['payeecontact'] = $paymentData['payeecontact'];
        }
        if ($orderType == 'BANK'){
            if (empty($paymentData['payeephone']) or empty($paymentData['bankswift'] or empty($paymentData['bankiban']))){
                return error_message(self::CODE_FAIL,self::STATUS_FAIL, "payeephone or bankswift or bankiban can't be empty");
            }
            $payload['payeephone'] = $paymentData['payeephone'];
            $payload['bankswift'] = $paymentData['bankswift'];
            $payload['bankiban'] = $paymentData['bankiban'];
        }
        if ($orderType == 'ach'){
            if (empty($paymentData['payeeBankRouting'])){
                return error_message(self::CODE_FAIL,self::STATUS_FAIL, "payeeBankRouting can't be empty");
            }
            $payload['payeeBankRouting'] = $paymentData['payeeBankRouting'];
        }
        if (in_array($orderType, ['ach', 'CASHAPPOUT', 'BANK']) )
        {
            if (empty($paymentData['payeeCountry'])){
                return error_message(self::CODE_FAIL,self::STATUS_FAIL, "payeeCountry can't be empty");
            }
            $payload['payeeCountry'] = $paymentData['payeeCountry'];
        }
        if (in_array($orderType, ['ach', 'BANK']) )
        {
            if (empty($paymentData['province']) or empty($paymentData['city'] or empty($paymentData['payeeAddress']) or empty($paymentData['payeePostalCode']))){
                return error_message(self::CODE_FAIL,self::STATUS_FAIL, "province or city or payeeAddress or payeePostalCode can't be empty");
            }
            $payload['province'] = $paymentData['province'];
            $payload['city'] = $paymentData['city'];
            $payload['payeeAddress'] = $paymentData['payeeAddress'];
        }
        if (!empty($inputdata['payeeaccount'])){
            $payload['payeeaccount'] = $inputdata['payeeaccount'];
        }
        if (!empty($inputdata['bankname'])){
            $payload['bankname'] = $inputdata['bankname'];
        }
        if (!empty($inputdata['subbranch'])){
            $payload['subbranch'] = $inputdata['subbranch'];
        }
        if (!empty($inputdata['accountname'])){
            $payload['accountname'] = $inputdata['accountname'];
        }

        $sign_data = sign($payload,$mid,$secret_key,false);
        $payload['sign'] = $sign_data;
        $header = getHeader();
        $res = vpost($gate_url, $header, $payload);
        if (!is_array($res)){
            $res = json_decode($res,true);
        }
        return $res;
    }

    public function query_payout($paymentData){
        $mid = '10010';
        $secret_key = '7e4nicn14nhyup146dfbi8hpnpus9juz';
        $gate_url = $this->config['dev_query_payout_url'];
        $is_debug = $this->config['env'];
        if (empty($paymentData['out_trade_no']) or empty($paymentData['transaction_id'])){
            return error_message(self::CODE_FAIL,self::STATUS_FAIL, "out_trade_no or transaction_id can't be empty");
        }
        if (!$is_debug){
            $gate_url = $this->config['query_payout_url'];
            $mid = $this->config['mid'];
            $secret_key = $this->config['skey'];
        }

        $payload = [
            "memberid" => $mid,
            "out_trade_no" => $paymentData['out_trade_no'],
            "transaction_id" => $paymentData['transaction_id'],
        ];

        $sign_data = sign($payload,$mid,$secret_key,false);
        $payload['sign'] = $sign_data;
        $header = getHeader();
        $res = vpost($gate_url, $header, $payload);
        if (!is_array($res)){
            $res = json_decode($res,true);
        }
        return $res;
    }




}