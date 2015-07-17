<?php
require '../../../vendor/autoload.php';

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\MySQL;

class myClass {

    function ajax()
    {
        include "../../_config.php";

        $dt = new Datatables(new MySQL($config), $this);

        $dt->query("Select film_id, title, description, rating, length from film");
        $dt->edit('description', "$1", 'sample_callback(film_id), strtolower(title)');

        echo $dt->generate();
    }

    function sample_callback($data)
    {
        return "returned film id: " . $data;
    }
}

$my_class = new myClass();
$my_class->ajax();


