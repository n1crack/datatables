<?php


namespace Ozdemir\Datatables;

use Ozdemir\Datatables\Http\Request;

/**
 * Class Option
 * @package Ozdemir\Datatables
 */
class Option
{
    /**
     * @var Request
     */
    private $request;

    /**
     * Option constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return int
     */
    public function draw(): int
    {
        return $this->request->get('draw') ?? 0;
    }

    /**
     * @return int
     */
    public function start(): int
    {
        return $this->request->get('start') ?? 0;
    }

    /**
     * @return int
     */
    public function length(): int
    {
        return $this->request->get('length') ?? 0;
    }

    /**
     * @return string
     */
    public function searchValue(): string
    {
        $search = $this->request->get('search') ?? [];

        return array_key_exists('value', $search) ? $search['value'] : '';
    }

    /**
     * @return array
     */
    public function order(): array
    {
        return $this->request->get('order') ?? [];
    }

    /**
     * @return array
     */
    public function columns(): array
    {
        return $this->request->get('columns') ?? [];
    }
}
