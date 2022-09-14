<?php

/**
 *@author: huajiao238
 *@email: 1032601165@qq.com
 */

class XunFeiRewriting
{
    protected $app_key = "你的api key";
    protected $app_secret = "你的api secret";
    protected $date = "";
    protected $url = "https://api.xf-yun.com/v1/private/se3acbe7f";
    protected $content = "要改的文本内容";
    protected $level = "L3";
    protected $app_id = "你的appID";


    public function __construct()
    {
        $this->date = gmdate('D, d M Y H:i:s') . ' GMT';

    }

    /**
     * 设置app key
     * @param string $app_key app key
     * @return $this
     */
    public function setAppKey(string $app_key): self
    {
        $this->app_key = $app_key;
        return $this;
    }


    /**
     * 设置app secret
     * @param string $app_secret app secret
     * @return $this
     */
    public function setAppSecret(string $app_secret): self
    {
        $this->app_secret = $app_secret;
        return $this;
    }

    /**
     * 设置app id
     * @param string $app_id app id
     * @return $this
     */
    public function setAppId(string $app_id): self
    {
        $this->app_id = $app_id;
        return $this;
    }

    /**
     * 设置改写内容
     * @param string $content 需要改写的内容
     * @return $this
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * 设置改写等级
     * @param string $level 改写等级（L1~L6）
     * @return $this
     */
    public function setLevel(string $level): self
    {
        $this->level = $level;
        return $this;
    }


    /**
     * hmac_sha256加密
     * @param string $content
     * @return string
     */
    private function hmacSha256Encode(string $content): string
    {
        return base64_encode(hash_hmac("sha256", $content, $this->app_secret, true));

    }

    /**
     * 从url中获取主机名
     * @return string
     */
    private function getHost(): string
    {
        $urlArray = parse_url($this->url);
        return $urlArray["host"];
    }

    /**
     * 从URL中获取协议头
     * @return string
     */
    private function getScheme(): string
    {
        $urlInfo = parse_url($this->url);
        return $urlInfo["scheme"];
    }

    /**
     * 拼接完整URL
     * @param string $authorization
     * @return string
     */
    private function getFullUrl(string $authorization): string
    {
        return $this->url . "?host=" . $this->getHost() . "&date=" . urlencode($this->date) . "&authorization=" . $authorization;
    }


    /**
     * 获取错误信息
     * @param int $key http code
     * @return string
     */
    private function getErrorMessage(int $key): string
    {
        $messageArray = [
            401 => "缺少authorization参数或签名参数解析失败",
            403 => "时钟偏移校验失败,检查服务器时间是否标准，相差5分钟以上会报此错误"
        ];
        if (array_key_exists($key, $messageArray)) {
            return $messageArray[$key];
        }
        return "未知错误";
    }

    public function get(): array
    {
        $signatureOrigin = "host: {$this->getHost()}\ndate: {$this->date}\nPOST /v1/private/se3acbe7f HTTP/1.1";
        $signatureSha = $this->hmacSha256Encode($signatureOrigin);
        $authorizationContent = [
            "api_key" => $this->app_key,
            "algorithm" => "hmac-sha256",
            "headers" => "host date request-line",
            "signature" => $signatureSha
        ];
        $authorization = "";
        foreach ($authorizationContent as $k => $v) {
            $authorization .= $k . '=' . '"' . $v . '",';
        }
        $authorization = base64_encode(rtrim($authorization, ","));
        $requestBody = [
            "header" => [
                "app_id" => $this->app_id,
                "status" => 3
            ],
            "parameter" => [
                "se3acbe7f" => [
                    "level" => "<" . $this->level . ">",
                    "result" => [
                        "encoding" => "utf8",
                        "compress" => "raw",
                        "format" => "json"
                    ]
                ]
            ],
            "payload" => [
                "input1" => [
                    "encoding" => "utf8",
                    "compress" => "raw",
                    "format" => "plain",
                    "status" => 3,
                    "text" => base64_encode($this->content)
                ]
            ]
        ];
        $url = $this->getFullUrl($authorization);
        return $this->request($url, $requestBody);
    }


    /**
     * 发送请求
     * @param string $url 请求地址
     * @param array $requestBody 请求体（数组）
     *
     */
    private function request(string $url, array $requestBody): array
    {
        $headerArray = array("Content-type:application/json;Host:{$this->getHost()}");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        if ($this->getScheme() == "https") {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        $requestError = curl_error($curl);
        if ($requestError) {
            return [
                "code" => 201,
                "message" => "请求错误"
            ];
        }
        $responseHeader = curl_getinfo($curl);
        if ($responseHeader["http_code"] != 200) {
            return [
                "code" => 201,
                "message" => $this->getErrorMessage($responseHeader["http_code"])
            ];
        }
        curl_close($curl);
        $responseBody = json_decode($response);
        if ($responseBody->header->code != 0) {
            return [
                "code" => 201,
                "message" => $responseBody->header->message
            ];
        }
        return [
            "code" => 200,
            "message" => base64_decode($responseBody->payload->result->text)
        ];
    }

}