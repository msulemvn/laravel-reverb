<?php

namespace App\DTO;

class BaseDTO
{
    public function toArray(): array
    {
        return (array) $this;
    }
}
