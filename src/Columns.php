<?php

namespace Ozdemir\Datatables;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class Columns
 *
 * @package Ozdemir\Datatables
 */
class Columns
{
    use Explode;

    /**
     * it contains all column objects
     * @var array
     *
     */
    private $container = [];

    /**
     * This is a list of column names, exclude hiddens.
     * @var array
     */
    public $list = [];

    /**
     * columns attributes from request
     * @var array|mixed
     */
    public $attr = [];

    /**
     * Columns constructor.
     *
     * @param $query
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function __construct($query, Request $request)
    {
        $columns = $this->setColumns($query);

        $this->attr = $request->get('columns');

        foreach ($columns as $name) {
            $this->addColumn($name);
        }
    }

    /**
     * It adds extra column for custom usage.
     *
     * @param $name
     * @return $this
     */
    public function add($name)
    {
        return $this->addColumn($name)->disableInteraction();
    }

    /**
     * @param $name
     * @return $this
     */
    protected function addColumn($name)
    {
        $this->list[] = $name;
        $this->container[] = new Column($name);

        return $this;
    }

    /**
     * Custom added columns can't interact with the db.
     *
     * @return $this
     */
    public function disableInteraction()
    {
        end($this->list);
        $key = key($this->list);
        $this->attr[$key]['searchable'] = false;
        $this->attr[$key]['orderable'] = false;

        return end($this->container);
    }

    /**
     * it returns Column object by searching it's name
     * @param $name
     * @param bool $includeHiddens
     * @return mixed
     */
    public function get($name, $includeHiddens = true)
    {

        $index = array_search($name, array_column($this->all($includeHiddens), 'name'));

        return $this->container[$index];
    }

    /**
     * it returns all column objects
     *
     * @param bool $includeHiddens
     * @return array
     */
    public function all($includeHiddens = true)
    {
        $activeColumns = array_filter($this->container, function ($c) {
            return ! $c->hidden;
        });

        return ($includeHiddens) ? $this->container : $activeColumns;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function value($name)
    {
        return $this->get($name)->closure;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function attr($name)
    {
        $index = array_search($name, array_column($this->all(false), 'name'));

        return $this->attr[$index];
    }

    /**
     * @param $name
     * @param $bool
     */
    public function hide($name, $bool)
    {

        array_splice($this->list, array_search($name, $this->list), 1);
        $this->get($name, true)->hidden = $bool;

        return;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function isSearchable($name)
    {
        return $this->attr($name)['searchable'];
    }

    /**
     * @param $name
     * @return mixed
     */
    public function isOrderable($name)
    {
        return $this->attr($name)['orderable'];
    }

    /**
     * @param $name
     * @return mixed
     */
    public function isSearching($name)
    {
        return $this->attr($name)['search']['value'];
    }

    /**
     * @param $query
     * @return null|string|string[]
     */
    protected function setColumns($query)
    {
        $query = preg_replace("/\((?:[^()]+|(?R))*+\)/is", "", $query);
        preg_match_all("/SELECT([\s\S]*?)((\s*)\bFROM\b(?![\s\S]*\)))([\s\S]*?)/is", $query, $columns);

        $columns = $this->explode(",", $columns[1][0]);

        // gets alias of the table -> 'table.column as col' or 'table.column col' to 'col'
        $regex[] = "/(.*)\s+as\s+(.*)/is";
        $regex[] = "/.+(\([^()]+\))?\s+(.+)/is";
        // wipe unwanted characters => '`" and space
        $regex[] = '/[\s"\'`]+/';
        // if there is no alias, return column name -> table.column to column
        $regex[] = "/([\w\-]*)\.([\w\-]*)/";

        return preg_replace($regex, "$2", $columns);
    }
}