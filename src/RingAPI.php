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

        $result = $this->getData($url,$taxNumber);

        if (!$result){
            $result = ['status' => 'error', 'msg' => 'Немає даних.', 'exception' => $this->exception];
        }

        return($result);
    }

    private function getData($url,$taxNumber)
    {
        try {

            $result=file_get_contents($url);
            $result=json_decode($result, true);
            $object = null;

            if (array_key_exists('search_results', $result)){
                $searchResults = $result['search_results'];
                unset($result);

                if (!array_key_exists('object_list', $searchResults)) return false;
                $objectList = $searchResults['object_list'];

                foreach ($objectList as $result){
                    //If tax number does not match go to next result
                    if ($result['full_edrpou']!=$taxNumber) continue;

                    $object = $result;
                    break;
                }

                if (!isset($object)) return false;

                $data = array();
                $data['address'] = $object['latest_record']['location'];
                $data['name'] = $object['latest_record']['name'];
                $data['status'] = $object['latest_record']['status'];
                $data['edrpou'] = $object['full_edrpou'];

                if (array_key_exists('raw_persons', $object)){
                    foreach ($object['raw_persons'] as $row){
                        $data['raw_persons'][] = $row;
                    }
                }

                if (array_key_exists('persons', $object)){
                    $rawPersons = $object['persons'];

                    //Leave only Cyrillic  //preg_match("/[а-я]/i", $str)
                    foreach ($rawPersons as $person) {
                        if(!preg_match("/[a-z]/i", $person)){
                            $persons[]=$person;
                        }
                    }
                    unset($rawPersons);

                    $data['persons'] = $persons;
                    unset($persons);
                }

                unset($searchResults);
                unset($objectList);
                unset($object);

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
