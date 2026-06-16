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
    public $customIndividualFilter;

    /**
     * Custom filter
     * @var \Closure
     */
    public $customGlobalFilter;

    /**
     *
     * @var string
     */
    public $customFilterType;

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
     * @return mixed the value can be a scalar or an array (e.g. orthogonal data)
     */
    public function value($row)
    {
        if ($this->closure instanceof \Closure) {
            $value = call_user_func($this->closure, $row);
        } else {
            $value = $row[$this->name] ?? null;
        }

        // Arrays (e.g. orthogonal data) are passed through untouched; scalars are
        // cast to string to preserve the previous behaviour and a null becomes ''.
        if (is_array($value)) {
            return $value;
        }

        return (string) $value;
    }

    /**
     * Set visibility of the column.
     * @param bool $searchable
     */
    public function hide(bool $searchable = false): void
    {
        $this->hidden = true;
        $this->forceSearch = $searchable;
    }

    /**
     * @return bool
     */
    public function hasCustomIndividualFilter(): bool
    {
        return $this->customIndividualFilter instanceof \Closure;
    }

    /**
     * @return bool
     */
    public function hasCustomGlobalFilter(): bool
    {
        return $this->customGlobalFilter instanceof \Closure;
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
     * @param string $property data as object, fallback data as string  
     * @return string
     */
    public function data($property = '_'): string
    {
        return $this->attr['data'][$property] ?? $this->attr['data']['_'] ?? $this->attr['data'] ?? '';
    }

    /**
     * @return string
     */
    public function searchValue(): string
    {
        return $this->attr['search']['value'] ?? '';
    }
}
