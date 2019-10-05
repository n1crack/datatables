<?php

namespace Ozdemir\Datatables\DB;

use Ozdemir\Datatables\Column;
use Ozdemir\Datatables\Query;
use PDO;

/**
 * Class PGSQL // PostgreSql Adapter
 * @package Ozdemir\Datatables\DB
 */
class PGSQL extends DBAdapter
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var array
     */
    protected $config;

    /**
     * MySQL constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @return $this
     */
    public function connect()
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $user = $this->config['username'];
        $pass = $this->config['password'];
        $database = $this->config['database'];

        $this->pdo = new PDO("pgsql:host=$host;dbname=$database;port=$port", "$user", "$pass");

        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $this;
    }

    /**
     * @param Query $query
     * @return mixed
     */
    public function query(Query $query)
    {
        $sql = $this->pdo->prepare($query);
        $sql->execute($query->escapes);

        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param Query $query
     * @return mixed
     */
    public function count(Query $query)
    {
        $sql = $this->pdo->prepare("Select count(*) as rowcount from ($query)t");
        $sql->execute($query->escapes);

        return (int)$sql->fetchColumn();
    }

    /**
     * @param $string
     * @param Query $query
     * @return string
     */
    public function escape($string, Query $query)
    {
        $query->escapes[':binding_'.(count($query->escapes) + 1)] = $string;

        return ':binding_'.count($query->escapes);
    }


    /**
     * @param Query $query
     * @param Column $column
     * @param $word
     * @return string
     */
    public function makeLikeString(Query $query, Column $column, string $word)
    {
        return $column->name.'::varchar ILIKE '.$this->escape('%'.$word.'%', $query);
    }
}

