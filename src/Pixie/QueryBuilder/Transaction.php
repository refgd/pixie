<?php

namespace Pixie\QueryBuilder;

class Transaction extends QueryBuilderHandler
{

    /**
     * Commit the database changes
     */
    public function commit()
    {
        $this->getPdo()->commit();
        throw new TransactionHaltException();
    }

    /**
     * Rollback the database changes
     */
    public function rollback()
    {
        $this->getPdo()->rollBack();
        throw new TransactionHaltException();
    }
}
