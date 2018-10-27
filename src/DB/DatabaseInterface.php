<?php

namespace Ozdemir\Datatables\DB;

use Ozdemir\Datatables\Query;

interface DatabaseInterface
{
    public function __construct($config);

    public function connect();

    public function query(Query $query);

    public function count(Query $query);

    public function escape($string, Query $query);
}
