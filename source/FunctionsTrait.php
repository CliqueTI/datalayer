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
                $params = ($params??"") . rtrim($field,'!<>=') . "={$value}&";
            }
            $terms = substr($terms, 0, -5);
            $params = substr($params, 0, -1);

            return ['terms'=>($this->statement?" AND ":"").$terms,'params'=>($this->params?"&":"").$params];
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
                    $params = ($params??"") . "{$rndField}={$value}&";
                }
                $terms = substr($terms, 0, -2);
                $params = substr($params, 0, -1);
            }

            $terms = ($this->statement?" AND ":"").key($data)." IN ({$terms})";
            $params = ($this->params?"&":"").$params;
            return ['terms'=>$terms,'params'=>$params];
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
                    $params = ($params??"") . "{$rndField}={$value}&";
                }
                $terms = substr($terms, 0, -2);
                $params = substr($params, 0, -1);
            }

            $terms = ($this->statement?" AND ":"").key($data)." NOT IN ({$terms})";
            $params = ($this->params?"&":"").$params;
            return ['terms'=>$terms,'params'=>$params];
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
                $params = ($params??"")."{$field}=%{$value}%&";
            }
            $terms = substr($terms, 0, -4);
            $params = substr($params, 0, -1);

            return ['terms'=>($this->statement?" AND ":"").$terms,'params'=>($this->params?"&":"").$params];
        }
        return null;
    }
    
}