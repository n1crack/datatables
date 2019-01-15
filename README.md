# Datatables library for PHP
[![Latest Stable Version](https://poser.pugx.org/ozdemir/datatables/v/stable)](https://packagist.org/packages/ozdemir/datatables) [![Build Status](https://travis-ci.org/n1crack/datatables.svg?branch=master)](https://travis-ci.org/n1crack/datatables) [![license](https://img.shields.io/github/license/mashape/apistatus.svg)](https://github.com/n1crack/datatables/blob/master/LICENCE) 

PHP Library to handle server-side processing for Datatables, in a fast and simple way. [Live Demo](https://datatables.ozdemir.be/)

## Features  
* Easy to use. Generates json using only a few lines of code.
* Editable columns with a closure function.
* Supports custom filters.
* Can handle most complicated queries.
* Supports mysql and sqlite for native php.
* Works with :
    - [Laravel](https://datatables.ozdemir.be/laravel)
    - [CodeIgniter 3](https://datatables.ozdemir.be/codeigniter)
    - [Phalcon 3+](https://datatables.ozdemir.be/phalcon)
    - [Prestashop](https://datatables.ozdemir.be/prestashop)

## Installation

> **NOTE:** version 2.0+ requires php 7.1.3+ ([php supported versions](http://php.net/supported-versions.php))

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

__query($query)__ *required*

* sets the sql query

__generate()__  *required*

* runs the queries and build outputs
* returns the output as json
* same as generate()->toJson()

__toJson()__

* returns the output as json
* should be called after generate()

__toArray()__

* returns the output as array
* should be called after generate()

__add($column, function( $row ){})__

* adds extra columns for custom usage

__edit($column, function($row){})__

* allows column editing

__filter($column, function(){})__

* allows custom filtering
* it has the methods below
    - escape($value)
    - searchValue()
    - defaultFilter()
    - between($low, $high)
    - whereIn($array)
    - greaterThan($value)
    - lessThan($value)

__hide($columns)__

* removes the column from output
* It is useful when you only need to use the data in add() or edit() methods.

__setDistinctResponseFrom($column)__

* executes the query with the given column name and adds the returned data to the output with the distinctData key.

__setDistinctResponse($output)__

* adds the given data to the output with the distinctData key.

__getColumns()__

* returns column names (for dev purpose)

__getQuery()__

* returns the sql query string that is created by the library (for dev purpose)


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

    $dt->filter('age', function (){
        // applies custom filtering.
        return $this->between(15, 30);
    });

    echo $dt->generate()->toJson(); // same as 'echo $dt->generate()';
```

## Requirements
Composer  
DataTables > 1.10  
PHP > 7.1.3

## License
Copyright (c) 2015 Yusuf ÖZDEMİR, released under [the MIT license](https://github.com/n1crack/Datatables/blob/master/LICENCE)
