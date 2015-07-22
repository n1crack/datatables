<?php
namespace Ozdemir\Datatables;

use Ozdemir\Datatables\DB\DatabaseInterface;

class Datatables {

    private $db;
    private $data;
    private $recordstotal;
    private $recordsfiltered;
    private $columns;
    private $edit;
    private $calledby;

    function __construct(DatabaseInterface $db, $calledby = null)
    {
        $this->calledby = $calledby;
        $this->db = $db->connect();
        $this->input = isset($_POST["draw"]) ? $_POST : $_GET;
    }

    public function query($query)
    {
        $this->setColumns($query);
        $columns = implode(", ", $this->columns);
        $query = str_replace(";", "", $query);
        $sql = "Select $columns from ($query)t";
        $this->recordstotal = $this->getCount($sql); // unfiltered data count is here.

        // filtering via global search
        $search = "";
        $globalsearch = $this->input('search')['value'];

        if ($globalsearch <> "")
        {
            $search = " WHERE (";
            foreach ($this->columns as $column)
            {
                $lookfor[] = $column . " LIKE '%" . $this->db->escape($globalsearch) . "%'";
            }
            $search .= implode(" OR ", $lookfor) . ")";
        }

        // todo: Individual column filtering

        $this->recordsfiltered = $this->getCount($sql . $search);  // filtered data count is here.

        $this->data = $this->db->query($sql . $search . $this->orderby() . $this->limit());

    }

    function setColumns($query)
    {
        preg_match_all("/SELECT([\s\S]*?)FROM([\s\S]*?)/i", $query, $columns);
        $columns = $this->explode(",", $columns[1][0]);
        $columns = preg_replace("/(.*)\s+as\s+(.*)/i", "$2", $columns);
        $columns = preg_replace("/(.+)(\([^()]+\))?\s+(.+)/i", "$3", $columns);
        $columns = preg_replace('/[\s"\'`]+/', '', $columns);
        $this->columns = preg_replace("/([\w\-]*)\.([\w\-]*)/", "$2", $columns);
    }

    private function getCount($query)
    {
        return $this->db->count($query);
    }

    private function limit()
    {
        $skip = (integer) $this->input('start');
        $take = (integer) $this->input('length');

        return " LIMIT $skip, $take ";
    }

    private function orderby()
    {
        $dtorders = $this->input('order');
        $orders = " ORDER BY ";

        if (is_array($dtorders))
        {
            foreach ($dtorders as $order)
            {
                $takeorders[] = $this->columns[ $order['column'] ] . " " . $this->db->escape($order['dir']);
            }
            $orders .= implode(",", $takeorders);
        } else
        { // nothing to order use default
            $orders .= $this->columns[0] . " asc";
        }

        return $orders;
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
                    if (in_array($argument, $this->columns))
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

            } elseif (in_array($replace, $this->columns))
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

    private function isIndexed() // if data source uses associative keys or index
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