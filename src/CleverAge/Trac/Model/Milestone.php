<?php

namespace CleverAge\Trac\Model;

class Milestone extends Model
{
    public function __construct(array $dataFromApi)
    {
        $this->parseFromApi($dataFromApi);
    }
}
