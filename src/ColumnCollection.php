<?php

namespace Ozdemir\Datatables;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class ColumnCollection
 *
 * @package Ozdemir\Datatables
 */
class ColumnCollection
{
    use Explode;

    /**
     * @var array
     */
    protected $pattern = [
        // gets alias of the table -> 'table.column as col' or 'table.column col' to 'col'
        "/(.*)\s+as\s+(.*)/is",
        "/.+(\([^()]+\))?\s+(.+)/is",
        // wipe unwanted characters => '`" and space
        '/[\s"\'`]+/',
        // if there is no alias, return column name -> table.column to column
        "/([\w\-]*)\.([\w\-]*)/",
    ];

    /**
     * it contains all column objects
     *
     * @var \ArrayObject
     *
     */
    private $container;

    /**
     * ColumnCollection constructor.
     *
     * @param string $query
     */
    public function __construct($query)
    {
        $this->container = new \ArrayObject();

        $columns = $this->setColumnNames($query);

        foreach ($columns as $name) {
            $this->container->append(new Column($name));
        }
    }

    /**
     * It adds extra column for custom usage.
     *
     * @param $name
     * @param $closure callable
     * @return Column
     */
    public function add($name, $closure): Column
    {
        $column = new Column($name);
        $column->closure = $closure;
        $column->interaction = false;

        $this->container->append($column);

        return $column;
    }

    /**
     * It edits columns
     *
     * @param $name
     * @param $closure callable
     * @return Column
     */
    public function edit($name, $closure): Column
    {
        $column = $this->get($name);
        $column->closure = $closure;

        return $column;
    }

    /**
     * It filters columns
     *
     * @param $name
     * @param $closure callable
     * @return Column
     */
    public function filter($name, $closure): Column
    {
        $column = $this->get($name);
        $column->customFilter = $closure;

        return $column;
    }

    /**
     * it returns Column object by its name
     *
     * @param $name
     * @return Column
     */
    public function get($name): Column
    {
        $names = array_column($this->all(true), 'name');
        $index = array_search($name, $names, true);

        return $this->container->offsetGet($index);
    }

    /**
     * it returns Column object by its index
     *
     * @param $index
     * @return Column
     */
    public function getByIndex($index): Column
    {
        $columns = $this->all(false);

        return current(\array_slice($columns, $index, 1));
    }

    /**
     * it returns all column objects
     *
     * @param bool $includeHidden
     * @return Column[]
     */
    public function all($includeHidden = true): array
    {
        $activeColumns = array_filter($this->container->getArrayCopy(), function ($c) {
            return !$c->hidden;
        });

        return $includeHidden ? $this->container->getArrayCopy() : $activeColumns;
    }

    /**
     * Assign column attributes
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function setAttributes(Request $request): void
    {
        foreach ($this->names() as $index => $name) {
            if (isset($request->get('columns')[$index])) {
                $this->get($name)->attr = $request->get('columns')[$index];
            } else {
                $this->get($name)->interaction = false;
            }
        }
    }

    /**
     * Get visible column names
     *
     * @return array
     */
    public function names(): array
    {
        return array_column($this->all(false), 'name');
    }

    /**
     *
     * @return Column[]
     */
    public function getSearchableColumns(): array
    {
        $columns = array_filter($this->container->getArrayCopy(), function (Column $c) {
            return !$c->hidden && $c->interaction && $c->attr['searchable'];
        });

        return $columns;
    }

    /**
     *
     * @return Column[]
     */
    public function getSearchableColumnsWithSearchValue(): array
    {
        $columns = array_filter($this->getSearchableColumns(), function (Column $c) {
            return $c->attr['search']['value'] !== '' || $c->customFilter;
        });

        return $columns;
    }

    /**
     * @param $query
     * @return array
     */
    protected function setColumnNames($query): array
    {
        $query = $this->removeAllEnclosedInParentheses($query);
        $columns = $this->getColumnArray($query);

        return $this->clearColumnNames($columns);
    }

    /**
     * @param $string
     * @return string
     */
    protected function removeAllEnclosedInParentheses($string): string
    {
        return preg_replace("/\((?:[^()]+|(?R))*+\)/i", '', $string);
    }

    /**
     * @param $string
     * @return array
     */
    protected function getColumnArray($string): array
    {
        preg_match_all("/SELECT([\s\S]*?)((\s*)\bFROM\b(?![\s\S]*\)))([\s\S]*?)/i", $string, $columns);

        return $this->explode(',', $columns[1][0]);
    }

    /**
     * @param $array
     * @return string[]
     */
    protected function clearColumnNames($array): array
    {
        return preg_replace($this->pattern, '$2', $array);
    }
}
