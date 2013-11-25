<?php

namespace CleverAge\Trac\Model;

class Ticket extends Model
{
    protected $tracUrl;

    public function __construct($tracUrl, array $dataFromApi)
    {
        $this->tracUrl = $tracUrl;
        $this->parseFromApi($dataFromApi);
    }

    public function getUrl()
    {
        return $this->tracUrl.'ticket/'.$this->id;
    }

    protected function parseFromApi(array $dataFromApi)
    {
        if (count($dataFromApi) === 4) {
            $this->set('id', $dataFromApi[0]);

            parent::parseFromApi($dataFromApi[3]);
        }
    }
}
