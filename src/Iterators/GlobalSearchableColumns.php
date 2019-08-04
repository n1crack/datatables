<?php

namespace Ozdemir\Datatables\Iterators;


use FilterIterator;

/**
 * Class GlobalSearchableColumns
 *
 * @package Ozdemir\Datatables\Iterators
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