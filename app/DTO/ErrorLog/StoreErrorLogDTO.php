<?php

namespace App\DTO\ErrorLog;

use App\DTO\BaseDTO;

class StoreErrorLogDTO extends BaseDTO
{
    public string $line_number;
    public string $exception_message;
    public string $file;
    public string $trace;
    public string $ip;

    public function __construct($request, $exception)
    {
        $this->line_number = $exception->getLine();
        $this->exception_message = $exception->getMessage();
        $this->file = $exception->getFile();
        $this->trace = $exception->getTraceAsString();
        $this->ip = $request->ip();
    }
}
