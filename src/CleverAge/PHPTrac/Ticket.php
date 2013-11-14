<?php

namespace CleverAge\PHPTrac;

class Ticket
{
    protected $data = array();
    protected $tracUrl;

    public function __construct($tracUrl, array $dataFromApi)
    {
        $this->tracUrl = $tracUrl;
        $this->parseFromApi($dataFromApi);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function get($name)
    {
        return array_key_exists($name, $this->data) ? $this->data[$name] : null;
    }

    public function set($name, $value)
    {
        $this->data[$name] = $value;

        return $this;
    }

    protected function parseFromApi(array $dataFromApi)
    {
        if (count($dataFromApi) === 4) {
            $this->set('id', $dataFromApi[0]);

            foreach ($dataFromApi[3] as $key => $value) {

                if (is_array($value) && array_key_exists('__jsonclass__', $value)) {
                    if ($value['__jsonclass__'][0] == 'datetime') {
                        $value = new \DateTime($value['__jsonclass__'][1]);
                    }
                }

                $this->set(strtolower($key), $value);
            }
        }
    }

    public function getUrl()
    {
        return $this->tracUrl.'ticket/'.$this->id;
    }
}
