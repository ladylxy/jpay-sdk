<?php

// 防止重复包含
if (!defined('COMMON_FUNCTIONS_DEFINED')) {
    define('COMMON_FUNCTIONS_DEFINED', true);

    // 检查函数是否已存在
    if (!function_exists('getClientIp')) {
        function getClientIp(): string
        {
            $ipHeaders = [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR'
            ];

            foreach ($ipHeaders as $header) {
                if (!empty($_SERVER[$header])) {
                    $ips = explode(',', $_SERVER[$header]);
                    $ip = trim($ips[0]);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }

            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }

    if (!function_exists('getUserAgent')) {
        /**
         * User-Agent
         */
        function getUserAgent(): string
        {
            return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        }
    }

    if (!function_exists('getReferer')) {
        function getReferer(): string
        {
            return $_SERVER['HTTP_REFERER'] ?? '';
        }
    }

    if (!function_exists('getDomainInfo')) {
        /**
         * get domain
         */
        function getDomainInfo()
        {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ($_SERVER['SERVER_PORT'] ?? null) == 443
                || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null) === 'https';

            $protocol = $isHttps ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'unknown';
            $fullDomain = $protocol . $host;

            $mainDomain = preg_replace('/^www\./', '', $host);
            return $fullDomain;
        }
    }

    if (!function_exists('checkRequiredFieldsWithMissing')) {
        function checkRequiredFieldsWithMissing(array $data, array $requiredKeys)
        {
            $missing = [];

            foreach ($requiredKeys as $key) {
                if (!isset($data[$key]) || $data[$key] === '' || $data[$key] === null) {
                    $missing[] = $key;
                }
            }

            return empty($missing) ? true : $missing;
        }
    }

    if (!function_exists('success_msg')) {
        /**
         * Create success response
         */
        function success_msg(int $code = null, string $status = null, string $message = null, array $data = []): array
        {
            return [
                'status' => $status??'success',
                'code' => $code ?? 0,
                'message' => $message ?? 'Operation completed successfully',
                'data' => $data,
                'timestamp' => time()
            ];
        }
    }

    if (!function_exists('error_message')) {
        function error_message(int $code = null, string $status = null, string $message = null, array $data = []): array
        {
            return [
                'status' => $status??'success',
                'code' => $code ?? -1,
                'message' => $message ?? 'Operation failed',
                'data' => $data,
                'timestamp' => time()
            ];
        }
    }

    if (!function_exists('vpost')) {
        function vpost($url, $header, $data=[]){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
            $res_data = curl_exec($ch);
            if (curl_error($ch)) {
                echo 'Curl error: ' . curl_error($ch);
            }
            if(!is_array($res_data)){
                $res_data = json_decode($res_data,true);
            }
            curl_close($ch);
            return $res_data;
        }
    }

    if (!function_exists('sign')) {
        function sign(array $payload,string $mid, string $api_secret, bool $isDefind){

            $data = [
                'pay_memberid' => $mid,
                'pay_orderid' => $payload['pay_orderid'],
                'pay_applydate' => $payload['pay_applydate'],
                'pay_bankcode' => '901',
                'pay_notifyurl' => $payload['pay_notifyurl'],
                'pay_callbackurl' => $payload['pay_callbackurl'],
                'pay_amount' => $payload['pay_amount'],
            ];
            if (!$isDefind) {
                $data = $payload;
            }
            ksort($data);
            $md5str = "";
            foreach ($data as $key => $val) {
                if (!empty($val)) {
                    $md5str = $md5str . $key . "=" . $val . "&";
                }
            }
            $sign_str = $md5str . 'key=' . $api_secret;
            $sign_data = strtoupper(md5($sign_str));
            return $sign_data;
        }
    }

    if (!function_exists('getHeader')) {
        function getHeader(){
            $header = [
                'Content-Type: application/x-www-form-urlencoded',
            ];

            // 安全地检查 HTTP_REFERER
            if (!empty($_SERVER['HTTP_REFERER'])) {
                $referer = 'Referer: ' . $_SERVER['HTTP_REFERER'];
                array_push($header, $referer);
            }

            return $header;
        }
    }

    if (!function_exists('writeLogs')) {
        function writeLogs($file_name, $logStr)
        {
            // 使用系统临时目录，或者允许用户配置日志路径
            $logDir = sys_get_temp_dir() . '/jpay-sdk/logs/';
            $dtMonth = date('Ym', time());
            createFolder($logDir . $dtMonth);
            $dNow = date('d', time());
            $fp = fopen($logDir . $dtMonth . '/' . $file_name . "_" . $dNow . ".txt", "a+");
            $logStr = date('Y-m-d H:i:s') . ' ' . $logStr;
            fwrite($fp, $logStr . "\r\n");
            fclose($fp);
        }
    }

    if (!function_exists('createFolder')) {
        function createFolder($path)
        {
            if (!file_exists($path))
            {
                createFolder(dirname($path));
                mkdir($path, 0777);
            }
        }
    }

} // endif COMMON_FUNCTIONS_DEFINED

?>