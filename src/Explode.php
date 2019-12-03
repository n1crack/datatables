<?php

namespace Ozdemir\Datatables;

/**
 * Trait Explode
 * @package Ozdemir\Datatables
 */
trait Explode
{
    /**
     * @param $str
     * @param $open
     * @param $close
     * @return int
     */
    protected static function balanceChars($str, $open, $close): int
    {
        $openCount = substr_count($str, $open);
        $closeCount = substr_count($str, $close);
        $retval = $openCount - $closeCount;

        return $retval;
    }

    /**
     * @param $delimiter
     * @param $str
     * @param string $open
     * @param string $close
     * @return array
     */
    protected static function explode($delimiter, $str, $open = '(', $close = ')'): array
    {
        $retval = [];
        $hold = [];
        $balance = 0;
        $parts = explode($delimiter, $str);
        foreach ($parts as $part) {
            $hold[] = $part;
            $balance += self::balanceChars($part, $open, $close);
            if ($balance < 1) {
                $retval[] = implode($delimiter, $hold);
                $hold = [];
                $balance = 0;
            }
        }
        if (count($hold) > 0) {
            $retval[] = implode($delimiter, $hold);
        }

        return $retval;
    }
}