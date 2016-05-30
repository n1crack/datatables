<?php
require '../../../vendor/autoload.php';

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\MySQL;

include "../../_config.php";

$dt = new Datatables(new MySQL($config));

$dt->query("Select 
  any_value(category.name) as category_name, 
  concat(sum(length), \" minutes\") as total_length 
from film 
left join film_category on film_category.film_id = film.film_id
left join category on film_category.category_id = category.category_id
group by category.category_id");

echo $dt->generate();
