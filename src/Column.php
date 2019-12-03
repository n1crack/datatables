<?php

namespace Ozdemir\Datatables;

/**
 * Class Column
 *
 * @package Ozdemir\Datatables
 */
class Column
{
    /**
     * Column name
     *
     * @var
     */
    public $name;

    /**
     * Column visibility
     *
     * @var bool
     */
    public $hidden = false;

    /**
     * Column seachable
     *
     * @var bool
     */
    public $forceSearch = false;

    /**
     * Callback function
     *
     * @var \Closure
     */
    public $closure;

    /**
     * @var array
     */
    public $attr = [];

    /**
     * @var bool
     */
    public $interaction = true;

    /**
     * Custom filter
     * @var \Closure
     */
    public $customFilter;

    /**
     * Column constructor.
     *
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->attr['searchable'] = false;
        $this->attr['orderable'] = false;
        $this->attr['search'] = ['value' => ''];
    }

    /**
     * @param $row array
     * @return string
     */
    public function value($row): string
    {
        if ($this->closure instanceof \Closure) {
            return call_user_func($this->closure, $row);
        }

        return $row[$this->name] ?? '';
    }

    /**
     * Set visibility of the column.
     */
    public function hide($searchable = false): void
    {
        $this->hidden = true;
        $this->forceSearch = $searchable;
    }

    /**
     * @return bool
     */
    public function hasFilter(): bool
    {
        return $this->customFilter instanceof \Closure;
    }

    /**
     * @return bool
     */
    public function isSearchable(): bool
    {
        return ($this->interaction && $this->attr['searchable'] === 'true');
    }

    /**
     * @return bool
     */
    public function isOrderable(): bool
    {
        return ($this->interaction && $this->attr['orderable'] === 'true');
    }

    /**
     * @return string
     */
    public function data(): string
    {
        return $this->attr['data'] ?? '';
    }

    /**
     * @return string
     */
    public function searchValue(): string
    {
        return $this->attr['search']['value'] ?? '';
    }
}
