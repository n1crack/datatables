<?php
require '../../../vendor/autoload.php';

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\MySQL;

include "../../_config.php";

$dt = new Datatables(new MySQL($config));

$dt->query("Select film_id, title, description from film");

$dt->editc('title', function($data){
    return '<a href="#id=' . $data['film_id'] . '">' . $data['title'] . '</a>';
});

echo $dt->generate();
