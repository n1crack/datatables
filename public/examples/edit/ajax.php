<?php
require '../../../vendor/autoload.php';

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\MySQL;

include "../../_config.php";

$dt = new Datatables(new MySQL($config));

$dt->query("Select film_id, title, description from film");

// edit 'title' column
$dt->edit('title', function ($data){
     return editurl($data['film_id'], 'edit') . '-' . strtolower($data['title']);
});

// edit 'description' column.
$dt->edit('description', function ($data){
    return substr($data['description'], 0, 25) . '...';
});

echo $dt->generate();


function editurl($id, $text)
{
    return '<a href="#' . $id . '">' . $text .'</a>';
}