<?php

namespace Ozdemir\Datatables\Iterators;

use ArrayIterator;
use Ozdemir\Datatables\Column;

/**
 * Class ColumnCollection
 *
 * @method Column current()
 * @package Ozdemir\Datatables
 */
class ColumnCollection extends ArrayIterator
{
    /**
     * @return ColumnCollection
     */
    public function visible(): ColumnCollection
    {
        $array = array_values(iterator_to_array(new VisibleColumns($this)));

        return new self($array);
    }

    /**
     * @return ColumnCollection
     */
    public function searchable(): ColumnCollection
    {
        $array = iterator_to_array(new GlobalSearchableColumns($this));

        return new self($array);
    }

    /**
     *
     * @return  ColumnCollection
     */
    public function individualSearchable(): ColumnCollection
    {
        $array = iterator_to_array(new IndividualSearchableColumns(new GlobalSearchableColumns($this)));

        return new self($array);
    }

    /**
     * it returns Column object by its name
     * @param $name
     * @return Column
     */
    public function getByName($name): Column
    {
        $lookup = array_column($this->getArrayCopy(), null, 'name');

        return $lookup[$name];
    }

    /**
     * it returns Column object by its name/index
     * @param $id
     * @return Column
     */
    public function get($id): Column
    {
        if ($this->offsetExists($id)) {
            return $this->offsetGet($id);
        }

        return $this->getByName($id);
    }

    /**
     * @param $id
     * @return bool
     */
    public function isExists($id): bool
    {
        return $this->offsetExists($id) || in_array($id, $this->names(), true);
    }

    /**
     * Get column names
     * @return array
     */
    public function names(): array
    {
        return array_column($this->visible()->getArrayCopy(), 'name');
    }
}
