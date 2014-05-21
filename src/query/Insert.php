<?php

namespace UniMapper\Query;

use UniMapper\Mapper,
    UniMapper\Entity,
    UniMapper\Exceptions\QueryException,
    UniMapper\Reflection;

class Insert extends \UniMapper\Query
{

    /** @var array */
    private $values = [];

    public function __construct(Reflection\Entity $entityReflection, Mapper $mapper, Entity $entity)
    {
        parent::__construct($entityReflection, $mapper);
        $this->values = $mapper->unmapEntity($entity);
        if (empty($this->values)) {
            throw new QueryException("Nothing to insert");
        }
    }

    public function getValues()
    {
        return $this->values;
    }

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        $primaryValue = $mapper->insert($mapper->getResource($this->entityReflection), $this->values);
        if ($primaryValue === null) {
            throw new QueryException("Insert should return primary value but null given!");
        }
        return $mapper->mapValue($this->entityReflection->getPrimaryProperty(), $primaryValue);
    }

}