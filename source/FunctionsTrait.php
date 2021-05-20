<?php


namespace CliqueTI\DataLayer;


/**
 * Trait FunctionsTrait
 * @package CliqueTI\DataLayer
 */
trait FunctionsTrait {

    /**
     * @param array|null $data
     * @return array|null
     */
    protected function where(array $data = null): ?array {
        if($data){
            foreach ($data as $field => $value){
                $terms = ($terms??"") . "{$field}=:".rtrim($field,'!<>=')." AND ";
                $params[rtrim($field,' !<>=')] = $value;
            }
            $terms = substr($terms, 0, -5);

            return ['terms'=>($this->statement?" AND ":"").$terms,'params'=>($params??[])];
        }
        return null;
    }


    /**
     * @param array|null $data
     * @return array|null
     */
    protected function whereIn(array $data = null): ?array {
        if($data){
            $field = key($data);
            $arValue = $data[$field];

            if(!is_array($arValue)){
                $arValue = explode(',',$arValue);
            }

            if(is_array($arValue)){
                foreach ($arValue as $value){
                    $rndField = "F".mt_rand(100,999);
                    $terms = ($terms??"") . ":{$rndField}, ";
                    $params[$rndField] = $value;
                }
                $terms = substr($terms, 0, -2);
            }

            $terms = ($this->statement?" AND ":"").key($data)." IN ({$terms})";
            return ['terms'=>$terms,'params'=>($params??[])];
        }
        return null;
    }

    /**
     * @param array|null $data
     * @return array|null
     */
    protected function whereNotIn(array $data = null): ?array {
        if($data){
            $field = key($data);
            $arValue = $data[$field];

            if(!is_array($arValue)){
                $arValue = explode(',',$arValue);
            }

            if(is_array($arValue)){
                foreach ($arValue as $value){
                    $rndField = "F".mt_rand(100,999);
                    $terms = ($terms??"") . ":{$rndField}, ";
                    $params[$rndField] = $value;
                }
                $terms = substr($terms, 0, -2);
            }

            $terms = ($this->statement?" AND ":"").key($data)." NOT IN ({$terms})";
            return ['terms'=>$terms,'params'=>($params??[])];
        }
        return null;
    }

    /**
     * @param array|null $data
     * @return array|null
     */
    protected function like(array $data = null): ?array {
        if($data){
            foreach ($data as $field => $value){
                $terms = ($terms??"")."{$field} LIKE :{$field} OR ";
                $params[$field] = "%{$value}%";
            }
            $terms = substr($terms, 0, -4);

            return ['terms'=>($this->statement?" AND ":"").$terms,'params'=>($params??[])];
        }
        return null;
    }
    
}