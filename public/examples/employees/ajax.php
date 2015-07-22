<?php
require '../../../vendor/autoload.php';

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\MySQL;

$config = ['host'     => 'localhost',
           'port'     => '3306',
           'username' => 'homestead',
           'password' => 'secret',
           'database' => 'employees'];

$dt = new Datatables(new MySQL($config));

$dt->query("SELECT first_name, last_name, Count(gender) as g FROM employees group by gender");

echo $dt->generate();
