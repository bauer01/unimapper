<?php

namespace UniMapper\Query;

use UniMapper\Entity,
    UniMapper\Reflection,
    UniMapper\Exceptions\QueryException;

/**
 * Insert query object
 */
class Insert extends \UniMapper\Query
{

    public $entity;
    public $returnPrimaryValue = true;

    public function __construct(Reflection\Entity $entityReflection, array $mappers, Entity $entity)
    {
        parent::__construct($entityReflection, $mappers);

        $requiredClass = $this->entityReflection->getName();
        if (!$entity instanceof $requiredClass) {
            throw new QueryException("Inserted entity must be instance of " . $requiredClass . " but " . $entity->getReflection()->getName() . "given!");
        }

        $this->entity = $entity;
    }

    public function onExecute()
    {
        if ($this->entityReflection->isHybrid()) {
            return $this->insertHybrid();
        }

        foreach ($this->entityReflection->getMappers() as $mapperName => $mapperReflection) {

            $result = $this->mappers[$mapperName]->insert($this);

            $primaryProperty = $this->entityReflection->getPrimaryProperty();
            if ($primaryProperty !== null) {
                $this->entity->{$primaryProperty->getName()} = $result;
            }

            return $this->entity;
        }
    }

    private function insertHybrid()
    {
        $primaryProperty = $this->entityReflection->getPrimaryProperty();
        if ($primaryProperty === null) {
            throw new QueryException("Primary property required for hybrid entity " . $this->entityReflection->getName() . "!");
        }

        $primaryValue = $this->entity->{$primaryProperty->getName()};
        foreach ($this->entityReflection->getMappers() as $mapperName => $mapperReflection) {

            if ($primaryValue) {
                $this->returnPrimaryValue = false;
            }

            $insertedPrimaryValue = $this->mappers[$mapperName]->insert($this);
            if ($insertedPrimaryValue) {
                // Set primary value automatically for all next mappers
                $this->entity->{$primaryProperty->getName()} = $primaryValue = $insertedPrimaryValue;
            }
        }

        return $this->entity;
    }

}