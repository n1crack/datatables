<?php

namespace Ozdemir\Datatables;

/**
 * Class ColumnNameList
 *
 * @package Ozdemir\Datatables
 */
class ColumnNameList
{
    use Explode;

    /**
     * @param $query
     * @return array
     */
    public static function from($query): array
    {
        $query = self::removeAllEnclosedInParentheses($query);
        $columns = self::getColumnArray($query);

        return self::clearColumnNames($columns);
    }

    /**
     * @param $string
     * @return string
     */
    protected static function removeAllEnclosedInParentheses($string): string
    {
        return preg_replace("/\((?:[^()]+|(?R))*+\)/", '', $string);
    }

    /**
     * @param $string
     * @return array
     */
    protected static function getColumnArray($string): array
    {
        preg_match("/SELECT(?P<columns>.*?)\s*\bFROM\b(?!.*\)).*/is", $string, $matches);

        return self::explode(',', $matches['columns']);
    }

    /**
     * @param $array
     * @return array
     */
    protected static function clearColumnNames($array): array
    {
        return preg_replace("/.*\b(\w+)['`\"\s]*$/s", '$1', $array);
    }
}