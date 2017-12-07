<?php

namespace Ozdemir\Datatables\DB;

/**
 * Interface DatabaseInterface
 * @package Ozdemir\Datatables\DB
 */
interface DatabaseInterface
{

    /**
     * DatabaseInterface constructor.
     * @param $config
     */
    public function __construct($config);

    public function connect();

    /**
     * @param $query
     * @return array
     */
    public function query($query);

    /**
     * @param $query
     * @return int
     */
    public function count($query);

    /**
     * @param $string
     * @return mixed
     */
    public function escape($string);

}
