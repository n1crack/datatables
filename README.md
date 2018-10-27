# Datatables library for PHP
[![Latest Stable Version](https://poser.pugx.org/ozdemir/datatables/v/stable)](https://packagist.org/packages/ozdemir/datatables) [![Build Status](https://travis-ci.org/n1crack/datatables.svg?branch=master)](https://travis-ci.org/n1crack/datatables) [![license](https://img.shields.io/github/license/mashape/apistatus.svg)](https://github.com/n1crack/datatables/blob/master/LICENCE) 

PHP Library to handle server-side processing for Datatables, in a fast and simple way. [Live Demo](http://datatables.16mb.com/)

## Features  
* Easy to use. Generates json using only a few lines of code.
* Editable columns with a closure function.
* Supports custom filters.
* Can handle most complicated queries.
* Supports mysql and sqlite for native php.
* Works with :
    - [Laravel](https://github.com/n1crack/datatables-examples/blob/master/other_examples/laravel.php)
    - [CodeIgniter 3](https://github.com/n1crack/datatables-examples/blob/master/other_examples/codeigniter.php)
    - [Phalcon 3+](https://github.com/n1crack/datatables-examples/blob/master/other_examples/phalcon.php)
    - [Prestashop](https://github.com/n1crack/datatables-examples/blob/master/other_examples/prestashop.php)

## Installation

The recommended way to install the library is with [Composer](https://getcomposer.org/)

If you haven't started using composer, I highly recommend you to use it.

Put a file named `composer.json` at the root of your project, containing this information: 

    {
        "require": {
           "ozdemir/datatables": "2.*"
        }
    }

And then run: 

```
composer install
```

Or just run : 

```
composer require ozdemir/datatables
```

Add the autoloader to your project:

```php
    <?php

    require_once 'vendor/autoload.php';
```

You're now ready to begin using the Datatables php library.

```php
    <?php
    require_once 'vendor/autoload.php';

    use Ozdemir\Datatables\Datatables;
    use Ozdemir\Datatables\DB\MySQL;

    $config = [ 'host'     => 'localhost',
                'port'     => '3306',
                'username' => 'homestead',
                'password' => 'secret',
                'database' => 'sakila' ];

    $dt = new Datatables( new MySQL($config) );

    $dt->query('Select film_id, title, description from film');

    echo $dt->generate();
```

## Methods
This is the list of available public methods.

| Methods  | Parameters | Usages |
| ------------- | ------------- | ------------- |
| `query($query)`  | $query: string  | *- required*<br>- sets the sql query   |
| `generate()`  | -  | *- required*<br>- runs the queries and build outputs  <br/>- returns the output as json,same as *generate()->toJson()* |
| `toJson()`  | -  | *- optional*<br>- returns the output as json |
| `toArray()`  | -  | *- optional*<br>- returns the output as array |
| `add($column,$function)` | $column:string,<br/>$function:callback  |  *- optional*<br>- allows adding extra columns for custom usage| 
| `edit($column,$function)` | $column:string,<br/>$function:callback  | *- optional*<br> - allows column editing | 
| `filter($column,$function)` | $column:string,<br/>$function:callback  | *- optional*<br> - allows custom filtering| 
| `hide($columns)` | $column:array/string | *- optional*<br>- removes the column from output, It is useful when you only need to use the data in add() and edit() methods.| 
| `getColumns()` | - |  *- optional*<br>- returns column names (for dev purpose) | 
| `getQuery()` | - |  *- optional*<br>- returns the sql query string that is created by the library (for dev purpose)| 

## Example

```php
    <?php
    require_once 'vendor/autoload.php';

    use Ozdemir\Datatables\Datatables;
    use Ozdemir\Datatables\DB\SQLite;

    $path = __DIR__ . '/../path/to/database.db';
    $dt = new Datatables( new SQLite($path) );

    $dt->query('Select id, name, email, age, address, plevel from users');

    $dt->edit('id', function($data){
        // return a link.
        return "<a href='user.php?id=" . $data['id'] . "'>edit</a>";
    });

    $dt->edit('email', function($data){
        // mask email : mail@mail.com => m***@mail.com
        return preg_replace('/(?<=.).(?=.*@)/u','*', $data['email']);
    });

    $dt->edit('address', function($data){
        // check if a user has authorized to see the column value.
        $current_user_plevel = 4;
        if ($current_user_plevel > 2 && $current_user_plevel > $data['plevel']) {
            return $data['address'];
        }

        return 'you are not authorized to view this column';
    });
    
    $dt->hide('plevel'); // hide 'plevel' column from the output

    $dt->add('action', function($data){
        // return a link in a new column
        return "<a href='user.php?id=" . $data['id'] . "'>edit</a>";
    });

    $datatables->filter('age', function ($escape, $search){
        // apply custom filtering.
        // ignore individual search value($search) for the column.
        // if you want to escape user inputs, you can use $escape($input)
        $val1 = 15;
        $val2 = 30;
        return "age BETWEEN $val1 AND $val2";
    });

    echo $dt->generate()->toJson(); // same as 'echo $dt->generate()';
```

## Requirements
Composer  
DataTables > 1.10  
PHP > 7.1.3

## License
Copyright (c) 2015 Yusuf ÖZDEMİR, released under [the MIT license](https://github.com/n1crack/Datatables/blob/master/LICENCE)
