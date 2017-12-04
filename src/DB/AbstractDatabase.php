<?php namespace Ozdemir\Datatables\DB;

use Ozdemir\Datatables\Bags\ErrorBag;

abstract class AbstractDatabase implements DatabaseInterface
{
    /**
     * @var ErrorBag
     */
    protected $errorBag;

    /**
     * @var array
     */
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->errorBag = new ErrorBag();
    }

    public function hasErrors()
    {
        return !$this->errorBag->isEmpty();
    }
}
