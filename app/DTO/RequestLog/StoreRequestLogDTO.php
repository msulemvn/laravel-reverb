<?php

namespace App\DTO\RequestLog;

use App\DTO\BaseDTO;

class StoreRequestLogDTO extends BaseDTO
{
    public string $session_id;
    public string $ip;
    public string $method;
    public string $url;
    public string $payload;
    public string $headers;

    public function __construct($request)
    {
        $this->session_id = session()->getId();
        $this->ip = $request->ip();
        $this->method = $request->method();
        $this->url = $request->fullUrl();
        $this->payload = json_encode($request->except(['password', 'password_confirmation', 'token']));
        $this->headers = json_encode($request->header());
    }
}
