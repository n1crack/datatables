<?php

namespace Ozdemir\Datatables\Iterators;


use FilterIterator;

/**
 * Class IndividualSearchableColumns
 *
 * @package Ozdemir\Datatables\Iterators
 */
class IndividualSearchableColumns extends FilterIterator
{
    /**
     * @return bool
     */
    public function accept()
    {
        return $this->current()->attr['search']['value'] !== '' || $this->current()->customFilter;
    }
}