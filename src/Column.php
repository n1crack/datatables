<?php

namespace Ozdemir\Datatables;

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
     * @var
     */
    public $closure;

    /**
     * Column constructor.
     *
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param $data
     * @param $field
     * @return mixed
     */
    public function closure($data, $field)
    {

        if (is_object($this->closure)) {
            $closure = $this->closure;

            return $closure($data);
        }

        return $data[$field];
    }
}