<?php
require '../../../vendor/autoload.php';

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\SQLite;

$app_path = realpath(dirname(__FILE__) . '/../../../');
$config = ['dir' => $app_path . '/db/sqlite-sakila.sqlite'];

// download sqlite-sakila.sqlite for the example from : https://dl.dropboxusercontent.com/u/48902075/sqlite-sakila.sqlite
// then set $config['dir'] as realpath of the db file.

$dt = new Datatables(new SQLite($config));

$dt->query("Select film_id as fid, title, description from film");

echo $dt->generate();
