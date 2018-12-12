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
    public function accept()
    {
        return !$this->current()->hidden && $this->current()->interaction && $this->current()->attr['searchable'];
    }
}