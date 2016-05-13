<?php

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\LaravelAdapter;

Route::get('/', function () {

    return view('welcome');

});

Route::get('ajax', function () {

    $datatables = new Datatables(new LaravelAdapter);

    $datatables->query('Select film_id, title, description from film');

    return $datatables->generate();

});
