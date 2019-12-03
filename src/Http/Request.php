<?php

namespace Ozdemir\Datatables\Http;

/**
 * Class Request
 * @package Ozdemir\Datatables\Http
 */
class Request
{
    /**
     * @var ParameterBag
     */
    public $request;

    /**
     * @var ParameterBag
     */
    public $query;

    /**
     * Request constructor.
     * @param array $request
     * @param array $query
     */
    public function __construct($request = array(), $query = array())
    {
        $this->request = new ParameterBag($request);
        $this->query = new ParameterBag($query);
    }

    /**
     * @return Request
     */
    public static function createFromGlobals(): Request
    {
        return self::create($_POST, $_GET);
    }

    /**
     * @param array $request
     * @param array $query
     * @return Request
     */
    public static function create($request = array(), $query = array()): Request
    {
        return new self($request, $query);
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function get($key)
    {
        if ($this->request->has($key)) {
            return $this->request->get($key);
        }

        if ($this->query->has($key)) {
            return $this->query->get($key);
        }

        return null;
    }
}