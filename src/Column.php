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
     * Callback function
     *
     * @var callable
     */
    public $closure;

    /**
     * @var array
     */
    public $attr;

    /**
     * @var bool
     */
    public $interaction = true;

    /**
     * Column constructor.
     *
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param $data
     * @param $field
     * @return mixed
     */
    public function closure($data, $field)
    {
        if ($this->closure) {
            $closure = $this->closure;

            return $closure($data);
        }

        return $data[$field];
    }

    /**
     * @return $this
     */
    public function hide()
    {
        $this->hidden = true;

        return $this;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function attr($value)
    {
        return $this->attr[$value];
    }

    /**
     * @return $this
     */
    public function disableInteraction()
    {
        $this->interaction = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSearchable()
    {
        return ($this->interaction && $this->attr['searchable']);
    }

    /**
     * @return bool
     */
    public function isOrderable()
    {
        return ($this->interaction && $this->attr['orderable']);
    }

    /**
     * @return string
     */
    public function searchValue()
    {
        return $this->attr['search']['value'];
    }
}