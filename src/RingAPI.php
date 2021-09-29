<?php

class RingAPI
{

    /**
     * Class RingAPI
     * API description
     * https://ring.org.ua/api
     */

    private $dataSources = ['edrdr'];
    private $format = 'json';
    private $exception;

    /**
     * @param $taxNumber
     * @return array
     *
     * sample return 1
     * array:2 [▼
     * "status" => "ok"
     * "data" => array:6 [▼
     * "address" => "Адреса Юридичної особи"
     * "name" => "Назва Юридичної особи"
     * "status" => "припинено"
     * "edrpou" => "12345678"
     * "raw_persons" => array:1 [▼
     * 0 => array:2 [▼
     * 0 => "ПРІЗВИЩЕ ІМЯ ПОБАТЬКОВІ"
     * 1 => "Посада"
     * ]
     * ]
     * "persons" => array:2 [▼
     * 0 => "Прізвище Імя Побатькові, Посада"
     * 1 => "Прізвище2 Імя Побатькові, Посада"
     * ]
     * ]
     * ]
     *
     * sample return 2
     * array:3 [▼
     * "status" => "error"
     * "msg" => "Немає даних."
     * "exception" => null
     * ]
     *
     */
    public function dataByTaxNumber($taxNumber)
    {

        $url  = "https://ring.org.ua/search";
        $url .= "?format=".$this->format;

        foreach ($this->dataSources as $source){
            $url .= "&datasources=".$source;
        }

        $url .= "&q=".$taxNumber;

        $result = $this->getData($url);

        if (!$result){
            $result = ['status' => 'error', 'msg' => 'Немає даних.', 'exception' => $this->exception];
        }

        return($result);
    }

    private function getData($url)
    {
        try {

            $result=file_get_contents($url);
            $result=json_decode($result, true);

            if (array_key_exists('search_results', $result)){
                $search_results = $result['search_results'];
                unset($result);

                if (!array_key_exists('object_list', $search_results)) return false;
                $object_list = $search_results['object_list'];

                //Get first only
                if (!array_key_exists('0', $object_list)) return false;
                $object_list = $object_list['0'];

                $data = array();
                $data['address'] = $object_list['latest_record']['location'];
                $data['name'] = $object_list['latest_record']['name'];
                $data['status'] = $object_list['latest_record']['status'];
                $data['edrpou'] = $object_list['full_edrpou'];

                if (array_key_exists('raw_persons', $object_list)){
                    foreach ($object_list['raw_persons'] as $row){
                        $data['raw_persons'][] = $row;
                    }
                }

                if (array_key_exists('persons', $object_list)){
                    $raw_persons = $object_list['persons'];

                    //Залишити тільки кирилицю //preg_match("/[а-я]/i", $str)
                    foreach ($raw_persons as $person) {
                        if(!preg_match("/[a-z]/i", $person)){
                            $persons[]=$person;
                        }
                    }
                    unset($raw_persons);

                    $data['persons'] = $persons;
                    unset($persons);
                }

                unset($search_results);
                unset($object_list);

                $result['status'] = 'ok';
                $result['data'] = $data;

                return ($result);
            }

        } catch (Exception $e) {
            $this->exception = $e->getMessage();
        }

        return (false);
    }
}
