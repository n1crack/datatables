<?php
include "../../../src/DB/DatabaseInterface.php";
include "../../../src/DB/MySQL.php";
include "../../../src/Datatables.php";

# no composer needed. just load these libraries and run.

include "../../_config.php";

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\MySQL;

$dt = new Datatables(new MySQL($config));

$dt->query("Select film_id as fid, title, description from film");

echo $dt->generate();
