<?php

namespace Ozdemir\Datatables\Http;

class Request
{

    public $request;

    public $query;

    public function __construct($request = array(), $query = array())
    {
        $this->request = new ParameterBag($request);
        $this->query = new ParameterBag($query);
    }

    public static function createFromGlobals(): Request
    {
        return self::create($_POST, $_GET);
    }

    public static function create($request = array(), $query = array()): Request
    {
        return new self($request, $query);
    }

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