<?php
require '../../../vendor/autoload.php';

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\MySQL;

include "../../_config.php";

$dt = new Datatables(new MySQL($config));

$dt->query("SELECT c.name as category, sum(p.amount) as total_sales FROM ((((payment p join rental r on p.rental_id = r.rental_id ) join inventory i on r.inventory_id = i.inventory_id ) join film f on i.film_id = f.film_id ) join film_category fc on f.film_id = fc.film_id ) join category c on fc.category_id = c.category_id  group by c.name;");

$dt->edit('total_sales', '$1 $', 'total_sales');

echo $dt->generate();
