<?php

namespace App\DTO\ActivityLog;

use App\DTO\BaseDTO;

class StoreActivityLogDTO extends BaseDTO
{
    public function __construct(
        public int $request_log_id,
        public string $description,
        public bool $showable,
    ) {}
}
