<?php

namespace UniMapper\Query;

class Delete extends \UniMapper\Query
{

    use Filterable;
    use Limit;

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $adapter = $connection->getAdapter($this->entityReflection->getAdapterName());

        $query = $adapter->createDelete(
            $this->entityReflection->getAdapterResource()
        );

        $this->setQueryFilters($this->filter, $query, $connection);

        return (int) $adapter->execute($query);
    }

}