<?php

namespace Goddard\Datatables\Iterators;


use FilterIterator;

/**
 * Class IndividualSearchableColumns
 *
 * @package Goddard\Datatables\Iterators
 */
class IndividualSearchableColumns extends FilterIterator
{
    /**
     * @return bool
     */
    public function accept(): bool
    {
        return $this->current()->searchValue() !== '' || $this->current()->hasFilter();
    }
}