<?php

namespace Ozdemir\Datatables\Iterators;


use FilterIterator;

/**
 * Class VisibleColumns
 *
 * @package Ozdemir\Datatables\Iterators
 */
class VisibleColumns extends FilterIterator
{

    /**
     * @return bool
     */
    public function accept(): bool
    {
        return !$this->current()->hidden;
    }
}