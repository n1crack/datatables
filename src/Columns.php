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
     *
     * @var \ArrayObject
     *
     */
    private $container;

    /**
     * Columns constructor.
     *
     * @param $query
     */
    public function __construct($query)
    {
        $this->container = new \ArrayObject();

        $columns = $this->setColumns($query);

        foreach ($columns as $name) {
            $this->container->append(new Column($name));
        }
    }

    /**
     * It adds extra column for custom usage.
     *
     * @param $name
     * @return \Ozdemir\Datatables\Column
     */
    public function add($name)
    {
        $this->container->append(new Column($name));

        return $this->get($name)->disableInteraction();
    }

    /**
     * it returns Column object by searching its name
     *
     * @param $name
     * @param bool $includeHiddens
     * @return \Ozdemir\Datatables\Column
     */
    public function get($name, $includeHiddens = true)
    {
        // php 5.6
        $names = array_map(function ($c) {
            return $c->name;
        }, $this->all($includeHiddens));

        $index = array_search($name, $names, true);

        // todo : array_column for array of objects only for php 7+
        // $index = array_search($name, array_column($this->all($includeHiddens), 'name'), true);

        return $this->container->offsetGet($index);
    }

    /**
     * it returns all column objects
     *
     * @param bool $includeHiddens
     * @return \Ozdemir\Datatables\Column[]
     */
    public function all($includeHiddens = true)
    {
        $activeColumns = array_filter($this->container->getArrayCopy(), function ($c) {
            return ! $c->hidden;
        });

        return $includeHiddens ? $this->container->getArrayCopy() : $activeColumns;
    }

    /**
     * Assign column attributes
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function attr(Request $request)
    {
        foreach ($this->names() as $index => $name) {
            $this->get($name)->attr = $request->get('columns')[$index];
        }
    }

    /**
     * Get visible column names
     *
     * @return array
     */
    public function names()
    {
        // php 5.6
        $names = array_map(function ($c) {
            return $c->name;
        }, $this->all(false));

        return array_values($names);

        // todo : array_column for array of objects only for php 7+
        // return array_column($this->all(false), 'name');
    }

    /**
     *
     * @return \Ozdemir\Datatables\Column[]
     */
    public function getSearchable()
    {
        $columns = array_filter($this->container->getArrayCopy(), function ($c) {
            return ! $c->hidden && $c->interaction && $c->attr['searchable'];
        });

        return $columns;
    }

    /**
     *
     * @return \Ozdemir\Datatables\Column[]
     */
    public function getSearchableWithSearchValue()
    {
        $columns = array_filter($this->getSearchable(), function ($c) {
            return $c->attr['search']['value'] !== '';
        });

        return $columns;
    }

    /**
     * @param $query
     * @return array
     */
    protected function setColumns($query)
    {
        $query = preg_replace("/\((?:[^()]+|(?R))*+\)/i", '', $query);
        preg_match_all("/SELECT([\s\S]*?)((\s*)\bFROM\b(?![\s\S]*\)))([\s\S]*?)/i", $query, $columns);

        $columns = $this->explode(',', $columns[1][0]);

        // gets alias of the table -> 'table.column as col' or 'table.column col' to 'col'
        $regex[] = "/(.*)\s+as\s+(.*)/is";
        $regex[] = "/.+(\([^()]+\))?\s+(.+)/is";
        // wipe unwanted characters => '`" and space
        $regex[] = '/[\s"\'`]+/';
        // if there is no alias, return column name -> table.column to column
        $regex[] = "/([\w\-]*)\.([\w\-]*)/";

        return preg_replace($regex, '$2', $columns);
    }
}