<?php
require '../../../vendor/autoload.php';

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\MySQL;

include "../../_config.php";

$dt = new Datatables(new MySQL($config));

$dt->query("Select film_id, title, description from film");

$dt->edit('title', "$2 - $1", 'editurl(film_id, edit), strtolower(title), title');
$dt->edit('description', '$1...', 'substr(description,0,25)');

echo $dt->generate();


function editurl($id, $text)
{
    return '<a href="#' . $id . '">' . $text .'</a>';
}