<?php
require '../../../vendor/autoload.php';

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\MySQL;

include "../../_config.php";

$dt = new Datatables(new MySQL($config));

$dt->query("Select film.film_id as fid, title, description, rental_rate from film left join film_category on film_category.film_id = film.film_id");

echo $dt->generate();
