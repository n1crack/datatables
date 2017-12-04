<?php namespace Ozdemir\Datatables\DB;

use PDO;
use PDOException;

/**
 * Class SQLite
 * @package Ozdemir\Datatables\DB
 */
class SQLite extends AbstractDatabase
{

    /** @var  PDO */
    protected $pdo;
    protected $config;
    protected $escape = [];

    /**
     * @return $this
     */
    public function connect()
    {
        try {
            $this->pdo = new PDO('sqlite:' . $this->config);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->errorBag->add($e->getMessage());
        } finally {
            return $this;
        }
    }

    /**
     * @param $query
     * @return array
     */
    public function query($query)
    {
        $sql = $this->pdo->prepare($query);
        $sql->execute($this->escape);
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $query
     * @return int
     */
    public function count($query)
    {
        $sql = $this->pdo->prepare($query);
        $sql->execute($this->escape);
        return count($sql->fetchAll());
    }

    /**
     * @param $string
     * @return string
     */
    public function escape($string)
    {
        $this->escape[':escape' . (count($this->escape) + 1)] = '%' . $string . '%';
        return ":escape" . (count($this->escape));
    }

}
