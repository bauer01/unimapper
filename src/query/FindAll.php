<?php

namespace UniMapper\Query;

use UniMapper\Exceptions\QueryException,
    UniMapper\Mapper,
    UniMapper\Reflection,
    UniMapper\EntityCollection,
    UniMapper\Query\IConditionable;

class FindAll extends \UniMapper\Query implements IConditionable
{

    public $limit = 0;
    public $offset = 0;
    public $orderBy = array();
    public $selection = array();

    public function __construct(Reflection\Entity $entityReflection, Mapper $mapper)
    {
        parent::__construct($entityReflection, $mapper);

        // Set selection
        $this->selection = array_slice(func_get_args(), 2);

        // Add primary property automatically if not set in selection
        if (count($this->selection) > 0) {
            $primaryPropertyName = $entityReflection->getPrimaryProperty()->getName();
            if (!in_array($primaryPropertyName, $this->selection)) {
                $this->selection[] = $primaryPropertyName;
            }
        }
    }

    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function orderBy($propertyName, $direction = "asc")
    {
        if (!$this->entityReflection->hasProperty($propertyName)) {
            throw new QueryException("Invalid property name '" . $propertyName . "'!");
        }

        $direction = strtolower($direction);
        if ($direction !== "asc" && $direction !== "desc") {
            throw new QueryException("Order direction must be 'asc' or 'desc'!");
        }
        $this->orderBy[] = array($propertyName, $direction);
        return $this;
    }

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        $this->beforeExecute();

        $result = $mapper->findAll($this);
        if ($result === false) {
            return new EntityCollection($this->entityReflection->getClassName());
        }

        return $result;
    }

    private function beforeExecute()
    {
        // Add properties from conditions to the selection if not set
        if (count($this->conditions) > 0 && count($this->selection) > 0) {
            foreach ($this->conditions as $condition) {

                list($propertyName) = $condition;
                if (!in_array($propertyName, $this->selection)) {
                    $this->selection[] = $propertyName;
                }
            }
        }
    }

}