<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Entity\Reflection;

class SelectOne extends \UniMapper\Query
{

    use Selectable;

    /** @var mixed */
    protected $primaryValue;

    public function __construct(
        Reflection $reflection,
        $primaryValue
    ) {
        parent::__construct($reflection);

        // Primary
        if (!$reflection->hasPrimary()) {
            throw new Exception\QueryException(
                "Can not use query on entity without primary property!"
            );
        }

        try {
            $reflection->getPrimaryProperty()->validateValueType($primaryValue);
        } catch (Exception\InvalidArgumentException $e) {
            throw new Exception\QueryException($e->getMessage());
        }

        $this->primaryValue = $primaryValue;

        // Selection
        $this->select(array_slice(func_get_args(), 3));
    }

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $adapter = $connection->getAdapter($this->reflection->getAdapterName());

        $primaryProperty = $this->reflection->getPrimaryProperty();

        $query = $adapter->createSelectOne(
            $this->reflection->getAdapterResource(),
            $primaryProperty->getUnmapped(),
            $connection->getMapper()->unmapValue(
                $primaryProperty,
                $this->primaryValue
            )
        );

        if ($this->adapterAssociations) {
            $query->setAssociations($this->adapterAssociations);
        }

        $result = $adapter->execute($query);

        if (!$result) {
            return false;
        }

        // Get remote associations
        if ($this->remoteAssociations) {

            settype($result, "array");

            foreach ($this->remoteAssociations as $colName => $association) {

                $assocValue = $result[$association->getKey()];

                $associated = $association->load($connection, [$assocValue]);

                // Merge returned associations
                if (isset($associated[$assocValue])) {
                    $result[$colName] = $associated[$assocValue];
                }
            }
        }

        return $connection->getMapper()->mapEntity($this->reflection->getName(), $result);
    }

}