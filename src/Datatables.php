<?php
namespace Ozdemir\Datatables;

use PHPSQLParser\PHPSQLCreator as Creator;
use PHPSQLParser\PHPSQLParser as Parser;
use Ozdemir\Datatables\DB\DatabaseInterface;

class Datatables {

    private $db;
    private $parser;
    private $data;
    private $creator;
    private $recordstotal;
    private $recordsfiltered;
    private $columns;
    private $wherelist;
    private $havinglist;
    private $edit;
    private $calledby;

    function __construct(DatabaseInterface $db, $calledby = null)
    {
        $this->calledby = $calledby;
        $this->db = $db->connect();
        $this->creator = new Creator();
        $this->parser = new Parser();
        $this->input = isset($_POST["draw"]) ? $_POST : $_GET;
    }

    public function query($query)
    {
        $records = $this->parser->parse($query);
        $this->setColumns($query);

        // Datatables handles ordering and limiting.
        unset($records["ORDER"]);
        unset($records["LIMIT"]);

        $this->recordstotal = $this->getCount($records); // unfiltered data count is here.


        if (isset($records["WHERE"]))
        {
            $records["WHERE"] = $this->putWhereinBrackets($records["WHERE"]);
            $records["WHERE"] = array_merge($records["WHERE"], array(['expr_type' => 'operator', 'base_expr' => 'and', 'sub_tree' => '']));
            $records["WHERE"] = array_merge($records["WHERE"], $this->globalfiltering());
        } else
        {
            $records["WHERE"] = $this->globalfiltering();
        }

        $this->recordsfiltered = $this->getCount($records);  // filtered data count is here.

        $records['ORDER'] = $this->orderby();

        // TODO: HAVING statement

        $records['LIMIT'] = $this->limit();
        $records_string = $this->creator->create($records);

        $this->data = $this->db->query($records_string);
    }

    public function generate()
    {
        $formatted_data = [];

        foreach ($this->data as $key => $row)
        {
            // editing columns..
            if (count($this->edit) > 0)
            {
                foreach ($this->edit as $edit_key => $edit_job)
                {
                    foreach ($edit_job as $edit_value)
                    {
                        $row[ $edit_key ] = $this->exec_replace($edit_value['content'], $edit_value['replacement'], $row);
                    }
                }
            }

            // todo: hide or show columns

            // Check datatables if it uses column names as data keys or not. todo: should be improved
            if ($this->isIndexed())
            {
                $formatted_data[] = array_values($row);
            } else
            {
                $formatted_data[] = $row;
            }
        }

        return $this->response($formatted_data);
    }

    public function edit($column, $content, $match_replacement)
    {
        $this->edit[ $column ][] = array('content' => $content, 'replacement' => $this->explode(',', $match_replacement));

        return $this;
    }

    private function input($input)
    {
        if (isset($this->input[ $input ]))
        {
            return $this->input[ $input ];
        }

        return false;
    }


    private function exec_replace($content, $replacements, $row_data)
    {
        if ( ! isset($replacements) && ! is_array($replacements))
        {
            return $content;
        }
        foreach ($replacements as $key => $replace)
        {
            $cleanup = "/(?<!\w)([\'\"])(.*)\\1(?!\w)/i";
            $replace = preg_replace($cleanup, '$2', trim($replace));
            //if this is a function.  matches test(arg1,arg2,..)
            if (preg_match('/(\w+::\w+|\w+)\((.*)\)/i', $replace, $matches) && (is_callable($matches[1]) || is_callable(array($this->calledby, $matches [1]))))
            {
                $function = $matches[1]; // set function name
                $arguments = preg_split("/[\s,]*\\\"([^\\\"]+)\\\"[\s,]*|" . "[\s,]*'([^']+)'[\s,]*|" . "[,]+/", $matches[2], 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                foreach ($arguments as $arg_index => $argument)
                {
                    $argument = preg_replace($cleanup, '$2', trim($argument)); // cleaning
                    if (array_key_exists($argument, $this->columns))
                    {
                        $arguments[ $arg_index ] = ($row_data[ $argument ]);
                    } else
                    {
                        $arguments[ $arg_index ] = $argument;
                    }
                }

                if (is_callable($function))
                {// is a function
                    $replace_string = call_user_func_array($function, $arguments);  // execute given function and return a value.
                } elseif (is_callable(array($this->calledby, $function)))
                {//is a method
                    $replace_string = call_user_func_array(array($this->calledby, $function), $arguments);
                }

            } elseif (array_key_exists($replace, $this->columns))
            { // if we have a $replace column
                $replace_string = $row_data[ $replace ];
            } else
            { // or just return the text.
                $replace_string = $replace;
            }
            // finally get the string and replace it with ${number} ( $1, $2 etc.)
            $content = preg_replace("/\\" . '$(' . ($key + 1) . "(?!\d))/i", $replace_string, $content);
        }

        return $content;
    }

    private function response($data)
    {
        $response['draw'] = $this->input('draw');
        $response['recordsTotal'] = $this->recordstotal;
        $response['recordsFiltered'] = $this->recordsfiltered;
        $response['data'] = $data;

        return json_encode($response);
    }

    private function globalfiltering()
    {
        $globalsearch = $this->input('search')['value'];

        if (isset($globalsearch))
        {
            $where = array();

            foreach ($this->wherelist as $column)
            {
                $where = array_merge($where, $this->addWhere($column, 'like', $globalsearch, (count($where) > 0)));
            }

            return $this->putWhereinBrackets($where);
        }

        return false;
    }

    private function addWhere($col_ref, $operator, $const, $add = false)
    {
        $where = [];

        $col_ref = $this->db->escape($col_ref);
        $operator = $this->db->escape($operator);
        $const = $this->db->escape($const);

        if ($add)
        {
            $where[] = ['expr_type' => 'operator', 'base_expr' => 'or', 'sub_tree' => ''];
        }
        $where[] = ['expr_type' => 'colref', 'base_expr' => "$col_ref", 'sub_tree' => ''];
        $where[] = ['expr_type' => 'operator', 'base_expr' => "$operator", 'sub_tree' => ''];
        $where[] = ['expr_type' => 'const', 'base_expr' => "'%$const%'", 'sub_tree' => ''];

        return $where;
    }

    private function limit()
    {
        $skip = (integer) $this->input('start');
        $take = (integer) $this->input('length');

        return ['offset'   => $skip,
                'rowcount' => $take];
    }

    private function orderby()
    {
        $dtorders = $this->input('order');

        $orders = [];

        if (is_array($dtorders))
        {
            foreach ($dtorders as $order)
            {
                $orders[] = ['expr_type' => 'colref',
                             'base_expr' => $this->getColumnAliases($order['column']),
                             'sub_tree'  => '',
                             'direction' => $this->db->escape($order['dir'])];
            }
        } else
        { // nothing to order use default
            $orders[] = ['expr_type' => 'colref',
                         'base_expr' => $this->getColumnAliases(0),
                         'sub_tree'  => '',
                         'direction' => 'asc'];
        }

        return $orders;
    }

    private function putWhereinBrackets($where)
    {
        $base_expr = [];
        foreach ($where as $item)
        {
            $base_expr[] = $item['base_expr'];
        }

        return array(['expr_type' => 'bracket_expression',
                      'base_expr' => '(' . implode(' ', $base_expr) . ')',
                      'sub_tree'  => $where]);
    }

    private function getColumnAliases($alias) // $alias = 0 gets first alias
    {
        return array_keys($this->columns)[ $alias ];
    }

    private function getCount($records)
    {
        $count = $this->parser->parse("Select *");
        $records["SELECT"] = $count["SELECT"];

        return $this->db->count($this->creator->create($records));
    }

    function setColumns($query)
    {
        $columns = strstr(strtolower($query), 'from', true);
        $columns = strstr(strtolower($columns), ' ', false);
        $colar = $this->explode(',', $columns);

        foreach ($colar as $col)
        {
            $split_by_alias = '/(.*)\s+as\s+(\w*)/i';
            $alias = trim(preg_replace($split_by_alias, '$2', $col));

            $this->columns[ $alias ] = trim(preg_replace($split_by_alias, '$1', $col));;
            if (strpos($col, '(') !== false)
            {
                $this->havinglist[ $alias ] = trim(preg_replace($split_by_alias, '$1', $col));
            } else
            {
                $this->wherelist[ $alias ] = trim(preg_replace($split_by_alias, '$1', $col));
            }
        }
    }

    private function isIndexed() // data source uses associative keys or index
    {
        $column = $this->input('columns');
        if (is_numeric($column[0]['data']))
        {
            return true;
        }

        return false;
    }

    private function balanceChars($str, $open, $close)
    {
        $openCount = substr_count($str, $open);
        $closeCount = substr_count($str, $close);
        $retval = $openCount - $closeCount;

        return $retval;
    }

    private function explode($delimiter, $str, $open = '(', $close = ')')
    {
        $retval = array();
        $hold = array();
        $balance = 0;
        $parts = explode($delimiter, $str);
        foreach ($parts as $part)
        {
            $hold[] = $part;
            $balance += $this->balanceChars($part, $open, $close);
            if ($balance < 1)
            {
                $retval[] = implode($delimiter, $hold);
                $hold = array();
                $balance = 0;
            }
        }
        if (count($hold) > 0)
        {
            $retval[] = implode($delimiter, $hold);
        }

        return $retval;
    }
}