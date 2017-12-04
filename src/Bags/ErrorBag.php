<?php


namespace Ozdemir\Datatables\Bags;


class ErrorBag extends AbstractIterator
{
    /**
     * @param string $element
     */
    public function add(string $element) : void
    {
        $this->bag[] = $element;
    }

    public function isEmpty() : bool
    {
        return empty($this->bag);
    }

    public function size()
    {
        return count($this->bag);
    }
}
