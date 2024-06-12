<?php

namespace Ozdemir\Datatables\DB;

use Ozdemir\Datatables\Column;
use Ozdemir\Datatables\Iterators\ColumnCollection;
use Ozdemir\Datatables\Query;

/**
 * Class Codeigniter4Adapter
 * @package Ozdemir\Datatables\DB
 */
class Codeigniter4Adapter extends DBAdapter
{
    /**
     * @var \CodeIgniter\Database\BaseConnection $db
     */
    protected $db;

    /**
     * @var array
     */
    protected $config;

    public function __construct($config = null)
    {
        $this->config = $config ?? ['DBGroup' => 'default'];
        $this->config['DBGroup'] = $this->config['DBGroup'] ?? 'default';
    }

    /**
     * @return $this
     */
    public function connect()
    {
        $this->db = \Config\Database::connect($this->config['DBGroup']);

        return $this;
    }

    /**
     * @param Query $query
     * @return mixed
     */
    public function query(Query $query)
    {
        $sql = $this->db->query($query, $query->escapes);

        return $sql->getResultArray();
    }

    /**
     * @param Query $query
     * @return mixed
     */
    public function count(Query $query)
    {
        $sql = $this->db->query("Select count(*) as rowcount from ($query)t", $query->escapes);

        return (int)$sql->getRow()->rowcount;
    }

    /**
     * @param $string
     * @param Query $query
     * @return string
     */
    public function escape($string, Query $query)
    {
        $query->escapes[] = $string;

        return '?';
    }

    /**
     * @param string $query
     * @param ColumnCollection $columns
     * @return string
     */
    public function makeQueryString(string $query, ColumnCollection $columns): string
    {
        if ($this->db->getPlatform() == 'Postgre') {
            return 'SELECT "'.implode('", "', $columns->names())."\" FROM ($query)t";
        }
        
        return 'SELECT `'.implode('`, `', $columns->names())."` FROM ($query)t";
    }

    /**
     * @param $query
     * @return string
     */
    public function getQueryString($query): string
    {
        if ($query instanceof \CodeIgniter\Database\BaseBuilder) {
            return $query->getCompiledSelect();
        }

        return $query;
    }

    /**
     * @param Query $query
     * @param Column $column
     * @param $word
     * @return string
     */
    public function makeLikeString(Query $query, Column $column, string $word)
    {
        if ($this->db->getPlatform() == 'Postgre') {
            return $column->name.'::TEXT ILIKE '.$this->escape('%'.$word.'%', $query);
        }

        return $column->name.' LIKE '.$this->escape('%'.$word.'%', $query);
    }
}
