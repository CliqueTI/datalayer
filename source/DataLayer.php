<?php

namespace CliqueTI\DataLayer;

use Exception;
use PDO;
use PDOException;
use stdClass;

/**
 * Class DataLayer
 * @package CliqueTI\DataLayer
 */
abstract class DataLayer {
    use CrudTrait;
    use FunctionsTrait;

    /** @var string $entity database table */
    private $entity;

    /** @var string $primary table primary key field */
    private $primary;

    /** @var array $required table required fields */
    private $required;

    /** @var string $timestamps control created and updated at */
    private $timestamps;

    /** @var array $listFields list fields of table */
    protected $listFields;

    /** @var string */
    protected $statement;

    /** @var string */
    protected $params;

    /** @var string */
    protected $groupby;

    /** @var string */
    protected $order;

    /** @var int */
    protected $limit;

    /** @var int */
    protected $offset;

    /** @var \PDOException|null */
    protected $fail;

    /** @var object|null */
    protected $data;

    /**
     * DataLayer constructor.
     * @param string $entity
     * @param array $required
     * @param string $primary
     * @param bool $timestamps
     */
    public function __construct(string $entity, array $required, string $primary = 'id', bool $timestamps = true) {
        $this->entity = $entity;
        $this->primary = $primary;
        $this->required = $required;
        $this->timestamps = $timestamps;
        $this->listFields = $this->getColunms();
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value) {
        if (empty($this->data)) {
            $this->data = new stdClass();
        }

        $this->data->$name = $value;
    }

    /**
     * @param $name
     * @return string|null
     */
    public function __get($name) {
        $method = $this->toCamelCase($name);
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        if (method_exists($this, $name)) {
            return $this->$name();
        }

        return ($this->data->$name ?? null);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name) {
        return isset($this->data->$name);
    }

    /**
     * @return object|null
     */
    public function data(): ?object {
        return $this->data;
    }

    /**
     * @return PDOException|Exception|null
     */
    public function fail() {
        return $this->fail;
    }

    /**
     * @param string|array $column
     * @return DataLayer|null
     */
    public function groupBy($column): ?DataLayer {
        if (is_array($column)) {
            $column = implode(", ", array_values($column));
        }
        $this->groupby = " GROUP BY {$column}";
        return $this;
    }

    /**
     * @param string|array $colOrder
     * @return DataLayer|null
     */
    public function order($colOrder): ?DataLayer {
        if (is_array($colOrder)) {
            foreach ($colOrder as $field => $order) {
                $strOrder = ($strOrder ?? "") . "{$field} {$order}, ";
            }
            $strOrder = substr($strOrder, 0, -2);
            $this->order = "ORDER BY {$strOrder}";
        } else {
            $this->order = "ORDER BY {$colOrder}";
        }
        return $this;
    }

    /**
     * @param int $limit
     * @return DataLayer|null
     */
    public function limit(int $limit): ?DataLayer {
        $this->limit = " LIMIT {$limit}";
        return $this;
    }

    /**
     * @param int $offset
     * @return DataLayer|null
     */
    public function offset(int $offset): ?DataLayer {
        $this->offset = " OFFSET {$offset}";
        return $this;
    }

    /**
     * @return int
     */
    public function count(): int {
        $stmt = Connect::getInstance()->prepare($this->statement);
        $stmt->execute($this->params);
        return $stmt->rowCount();
    }

    /**
     * @return array|null
     */
    public function getColunms() {
        $rs = Connect::getInstance()->query("SELECT * FROM {$this->entity} LIMIT 0");
        for ($i = 0; $i < $rs->columnCount(); $i++) {
            $col = $rs->getColumnMeta($i);
            $columns[] = $col['name'];
        }
        return ($columns ?? null);
    }


    /**
     * @param array|null $terms
     * @param string|null $params
     * @param string $columns
     * @param bool $distinct
     * @return DataLayer|null
     */
    public function find($terms = null, string $params = null, string $columns = "*", bool $distinct = false) {

        if (is_array($terms)) {

            foreach ($terms as $function => $term) {
                $fnc = $this->toCamelCase($function);
                if (method_exists($this, $fnc)) {
                    $response = $this->$fnc($term);
                    $this->statement .= $response['terms'];
                    $this->params = array_merge(($this->params??[]),$response['params']);
                }
            }
            $this->statement = "WHERE {$this->statement}";

        } elseif ($terms) {

            $this->statement .= "WHERE {$terms}";
            parse_str($params, $this->params);

        }

        $distinct = ($distinct ? "DISTINCT " : "");
        $distinct = ($distinct ? "DISTINCT " : "");
        $this->statement = "SELECT {$distinct}{$columns} FROM {$this->entity} {$this->statement}";
        return $this;
    }

    /**
     * @param int $id
     * @param string $columns
     * @return null|mixed|DataLayer
     */
    public function findById(int $id, string $columns = "*"): ?DataLayer {
        return $this->find(['where' => [$this->primary => $id]], null, $columns)->fetch();
    }

    /**
     * @param array $listPost
     * @param bool $checkFieldExist
     * @return $this
     */
    public function setFromForm(array $listPost, bool $checkFieldExist = true): DataLayer {
        foreach ($listPost as $field => $value) {
            if (!empty($this->listFields)) {
                if (in_array($field, $this->listFields)) {
                    $this->{$field} = $value;
                }
            } elseif(!$checkFieldExist) {
                $this->{$field} = $value;
            }
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function save(): bool {
        /* Vars */
        $primary = $this->primary;
        $id = null;
        /* Execute */
        try {
            if (!$this->required()) {
                throw new Exception("Preencha os campos necessários.");
            }
            /* Update */
            if (!empty($this->data->$primary)) {
                $id = $this->data->$primary;
                $this->update($this->safe(), "{$this->primary} = :id", "id={$id}");
            }
            /* Create */
            if (empty($this->data->$primary)) {
                $id = $this->create($this->safe());
            }
            /* Exit */
            if ($id) {
                $this->data = $this->findById($id)->data();
                return true;
            }
            return false;

        } catch (Exception $exception) {
            $this->fail = $exception;
            return false;
        }
    }

    /**
     * @return bool
     */
    public function destroy(): bool {
        $primary = $this->primary;
        $id = $this->data->$primary;

        if(empty($id)) {
            return false;
        }

        return $this->delete("{$this->primary} = :id", "id={$id}");
    }


    /**
     * @return array|null
     */
    protected function safe(): ?array {
        $safe = (array)$this->data;
        unset($safe[$this->primary]);
        return $safe;
    }

    /**
     * @return bool
     */
    protected function required(): bool {
        $data = (array)$this->data();
        foreach ($this->required as $field) {
            if(!array_key_exists($field, $data)){
                return false;
            }
            if (empty(trim($data[$field]))) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $string
     * @return string
     */
    protected function toCamelCase(string $string): string {
        $camelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        $camelCase[0] = strtolower($camelCase[0]);
        return $camelCase;
    }

}