<?php namespace Ozdemir\Datatables\DB;

use mysqli;
use Exception;

class MySQL implements DatabaseInterface {

    private $mysqli;
    private $config;

    function __construct($config)
    {
        $this->config = $config;
    }

    public function connect()
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $user = $this->config['username'];
        $pass = $this->config['password'];
        $database = $this->config['database'];
        $charset = 'utf8';

        $this->mysqli = new mysqli($host, $user, $pass, $database, $port);
        $this->mysqli->set_charset($charset);

        if (mysqli_connect_errno())
        {
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit();
        }

        return $this;
    }

    public function query($query)
    {
        $result = $this->mysqli->query($query);
        if ( ! $result)
        {
            throw new Exception("Database Error [{$this->mysqli->errno}] {$this->mysqli->error}");
        }
        $rows = [];
        while ($row = $result->fetch_assoc())
        {
            $rows[] = $row;
        }

        return $rows;
    }

    public function count($query)
    {
        $result = $this->mysqli->query($query);

        return $this->mysqli->affected_rows;
    }

    public function escape($string)
    {
        return mysqli_real_escape_string($this->mysqli, $string);
    }

}