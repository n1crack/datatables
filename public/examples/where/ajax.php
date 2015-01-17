<?php
require '../../../vendor/autoload.php';

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\MySQL;

include "../../_config.php";

$dt = new Datatables(new MySQL($config));

$dt->query("Select film_id as fid, title, description from film where film_id > 47 and film_id < 83");

echo $dt->generate();
