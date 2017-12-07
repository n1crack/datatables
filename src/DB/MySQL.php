<?php namespace Ozdemir\Datatables\DB;

use PDO;
use PDOException;

/**
 * Class MySQL
 * @package Ozdemir\Datatables\DB
 */
class MySQL extends AbstractDatabase
{

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var array
     */
    protected $config = null;

    /**
     * @var array
     */
    protected $escape = [];

    /**
     * @var array
     */
    protected $bindings = [];

    /**
     * @return $this
     */
    public function connect()
    {
        $host = $this->config['host'];
        $port = isset($this->config['port']) ?: 3306;
        $user = $this->config['username'];
        $pass = $this->config['password'];
        $database = $this->config['database'];
        $charset = 'utf8';

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$database;port=$port;charset=$charset", "$user", "$pass");
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->errorBag->add($e->getMessage());
        } finally {
            return $this;
        }
    }

    /**
     * @param $query
     * @param array $bindings
     * @return array
     */
    public function query($query, $bindings = [])
    {
        $sql = $this->pdo->prepare($query);

        $sql->execute(array_merge($this->bindings, $this->escape));

        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $query
     * @param array $bindings
     * @return int
     */
    public function count($query, $bindings = [])
    {
        $sql = $this->pdo->prepare($query);

        $sql->execute(array_merge($this->bindings, $this->escape));

        return $sql->rowCount();
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

    /**
     * @param string $parameter
     * @param string $value
     * @return void
     */
    public function bind($parameter, $value)
    {
        $this->bindings[$parameter] = $value;
    }

}
