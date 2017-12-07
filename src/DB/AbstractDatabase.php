<?php namespace Ozdemir\Datatables\DB;

use Ozdemir\Datatables\Bags\Bag;

/**
 * Class AbstractDatabase
 * @package Ozdemir\Datatables\DB
 */
abstract class AbstractDatabase implements DatabaseInterface
{
    /**
     * @var Bag
     */
    protected $errorBag;

    /**
     * @var array
     */
    protected $config;

    /**
     * AbstractDatabase constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->errorBag = new Bag();
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return !$this->errorBag->isEmpty();
    }
}
