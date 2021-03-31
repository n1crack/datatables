<?php

namespace Goddard\Datatables\Iterators;


use FilterIterator;

/**
 * Class VisibleColumns
 *
 * @package Goddard\Datatables\Iterators
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