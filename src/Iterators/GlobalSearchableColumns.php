<?php

namespace Goddard\Datatables\Iterators;


use FilterIterator;

/**
 * Class GlobalSearchableColumns
 *
 * @package Goddard\Datatables\Iterators
 */
class GlobalSearchableColumns extends FilterIterator
{
    /**
     * @return bool
     */
    public function accept(): bool
    {
        return ($this->current()->forceSearch || (!$this->current()->hidden && $this->current()->isSearchable()));
    }
}