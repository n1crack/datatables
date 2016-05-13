<?php
require '../../../vendor/autoload.php';

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\MySQL;

class myClass {

    function ajax()
    {
        include "../../_config.php";

        $dt = new Datatables(new MySQL($config));

        $dt->query("Select film_id, title, description, rating, length from film");

        $dt->edit('description', function ($data){
            return $this->sample_callback($data['description']);
        });

        $dt->edit('title', function ($data){
            return strtolower($data['title']);
        });

        echo $dt->generate();
    }

    function sample_callback($data)
    {
        return "returned film id: " . $data;
    }
}

$my_class = new myClass();
$my_class->ajax();


