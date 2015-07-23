<?php
require '../../../vendor/autoload.php';

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\MySQL;

# 300k data example.
# http://dev.mysql.com/doc/employee/en/employees-installation.html

$config = ['host'     => 'localhost',
           'port'     => '3306',
           'username' => 'homestead',
           'password' => 'secret',
           'database' => 'employees'];

$dt = new Datatables(new MySQL($config));

$dt->query("SELECT first_name, last_name, birth_date bd FROM employees;");

echo $dt->generate();
