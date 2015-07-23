# Datatables library for PHP

This is a php library that handles server-side processing for DataTables which is plug-in for the jQuery Javascript library.

## Features  
1. Easy to use. Generates json using only a few lines of code.  
2. Editable custom variables with callback function (or method) support.  
3. Auto detects HTTP method (POST or GET)  

## How to install?

Installation via composer is supported.  [Composer](http://https://getcomposer.org/).

Put a file named `composer.json` at the root of your project, containing this information: 

    {
        "require": {
           "ozdemir/datatables": "~1.*"
        }
    }

And then run: `composer install`

Add the autoloader to your project:

    <?php
    
    require_once 'vendor/autoload.php'


You're now ready to begin using the Datatables php library. 


## How to use?  

A simple ajax example:

    <?php
    require_once 'vendor/autoload.php';
    
    use Ozdemir\Datatables\Datatables;
    use Ozdemir\Datatables\DB\MySQL;
    
    $config = [ 'host'     => 'localhost',
                'post'     => '3306',
                'username' => 'homestead',
                'password' => 'secret',
                'database' => 'sakila' ];
                
    $dt = new Datatables( new MySQL($config) );
    
    $dt->query("Select film_id, title, description from film");
    
    echo $dt->generate();
    


There are several examples in the `public/examples` folder.

#### Methods  
This is the list of available public methods.

##### query (<code> $query : string </code>) `(required)`  
##### edit (<code>$column:string</code>, <code>$content:string</code>, <code>$matches:string</code>) `(optional)`   
##### generate ( ) `(required)`  

## Requirements

DataTables > 1.10  
PHP > 5.3.7  


## License  

The MIT License (MIT)

Copyright (c) 2015 Yusuf ÖZDEMİR

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
