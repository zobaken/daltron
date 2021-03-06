<?php

namespace Dal\Query;

/**
 * Abstract query implementation.
 *
 * @TODO Add 'insertRow', 'updateRow' methods
 *
 * @method \Dal\Query\Basic and(...$arguments) Method hint
 * @method \Dal\Query\Basic begin(...$arguments) Method hint
 * @method \Dal\Query\Basic commit(...$arguments) Method hint
 * @method \Dal\Query\Basic create(...$arguments) Method hint
 * @method \Dal\Query\Basic createDatabase(...$arguments) Method hint
 * @method \Dal\Query\Basic createTable(...$arguments) Method hint
 * @method \Dal\Query\Basic delete(...$arguments) Method hint
 * @method \Dal\Query\Basic deleteFrom(...$arguments) Method hint
 * @method \Dal\Query\Basic drop(...$arguments) Method hint
 * @method \Dal\Query\Basic dropDatabase(...$arguments) Method hint
 * @method \Dal\Query\Basic dropTable(...$arguments) Method hint
 * @method \Dal\Query\Basic from(...$arguments) Method hint
 * @method \Dal\Query\Basic groupBy(...$arguments) Method hint
 * @method \Dal\Query\Basic having(...$arguments) Method hint
 * @method \Dal\Query\Basic insert(...$arguments) Method hint
 * @method \Dal\Query\Basic insertInto(...$arguments) Method hint
 * @method \Dal\Query\Basic into(...$arguments) Method hint
 * @method \Dal\Query\Basic limit(...$arguments) Method hint
 * @method \Dal\Query\Basic not(...$arguments) Method hint
 * @method \Dal\Query\Basic null(...$arguments) Method hint
 * @method \Dal\Query\Basic offset(...$arguments) Method hint
 * @method \Dal\Query\Basic or(...$arguments) Method hint
 * @method \Dal\Query\Basic orderBy(...$arguments) Method hint
 * @method \Dal\Query\Basic replace(...$arguments) Method hint
 * @method \Dal\Query\Basic rollback(...$arguments) Method hint
 * @method \Dal\Query\Basic select(...$arguments) Method hint
 * @method \Dal\Query\Basic set(...$arguments) Method hint
 * @method \Dal\Query\Basic update(...$arguments) Method hint
 * @method \Dal\Query\Basic where(...$arguments) Method hint
 */
abstract class Basic {

    /**
     * @var string Sql statement
     */
    public $sql = '';

    /**
     * @var \stdClass Database configuration object
     */
    public $cfg = null;

    /**
     * @var string Class name for result objects
     */
    public $classname;

    /**
     * Set class name for created objects
     * @param string $class
     * @return \Dal\Query\Basic
     */
    public function setClass($class) {
        $this->classname = $class;
        return $this;
    }

    /**
     * Replace placeholders with values
     * @param string $text
     * @param array $args
     * @return string
     */
    public function parse($text, $args) {
        $i = 0;
        $me = $this;
        $text = preg_replace_callback('|#\?|', function() use (&$i, $args, $me) {
            return $me->quoteName($args[$i++]);
        }, $text);
        $text = preg_replace_callback('|\!\?|', function() use (&$i, $args, $me) {
            return $args[$i++];
        }, $text);
        return preg_replace_callback('|\?|', function() use (&$i, $args, $me) {
            return $me->quote($args[$i++]);
        }, $text);
    }

    /**
     * Quote database name
     * @param string $name
     * @return string
     */
    public function quoteName($name) {
        return $name;
    }

    /**
     * Append query text from array of arguments
     * @param array $args
     * @return \Dal\Query\Basic
     */
    public function queryArgs(array $args) {
        if (count($args) > 1) {
            $this->sql .= $this->parse($args[0], array_slice($args, 1)) . "\n";
        } else {
            $this->sql .= $args[0] . "\n";
        }
        return $this;
    }

    /**
     * Append query text and replace placeholders
     * @param string $text
     * @return \Dal\Query\Basic
     */
    public function query($text) {
        if (func_num_args() > 1) {
            $this->sql .= $this->parse($text, array_slice(func_get_args(), 1)) . "\n";
        } else {
            $this->sql .= $text . "\n";
        }
        return $this;
    }

    /**
     * Append query if condition is positive
     * @param mixed $condition
     * @param string $text
     * @return \Dal\Query\Basic
     */
    public function ifQuery($condition, $text) {
        if ($condition) {
            $args = array_slice(func_get_args(), 1);
            return $this->queryArgs($args);
        }
        return $this;
    }

    /**
     * Get sql text
     * @return string
     */
    public function asSql() {
        return $this->sql;
    }

    /**
     * Magically append query with method name and text, replace placeholders
     * @param string $name
     * @param array $args
     * @return \Dal\Query\Basic
     */
    public function __call($name, $args) {
        $line = $name;
        $offset = 0;
        $words = [];
        while(preg_match('/([A-Za-z][a-z]*)(_*)/', $line, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $words []= strtoupper($m[1][0]);
            $offset = $m[0][1] + strlen($m[0][0]);
        }
        $args[0] = implode(' ', $words) . (isset($args[0]) ? (' ' . $args[0]) : '');
        return $this->queryArgs($args);
    }

    // Shortcut functions

    /**
     * Fetch row as object
     * @param string $class Result type
     * @return \stdClass
     */
    public function fetchRow($class = null) {
        return call_user_func_array([$this, 'fetchObject'], func_get_args());
    }

    /**
     * Fetch all rows as objects
     * @param string $class Result objects type
     * @return array
     */
    public function fetchAll($class = null) {
        return call_user_func_array([$this, 'fetchAllObject'], func_get_args());
    }

    /**
     * Append query text and replace placeholders
     * @param string $text
     * @return \Dal\Query\Basic
     */
    public function q($text) {
        return $this->queryArgs(func_get_args());
    }

    /**
     * Append query if condition is positive
     * @param mixed $condition
     * @param string $text
     * @return \Dal\Query\Basic
     */
    public function ifQ($condition, $text) {
        return call_user_func_array([$this, 'ifQuery'], func_get_args());
    }

    // Abstract methods

    /**
     * Quete database value
     * @param mixed $val
     * @return string
     */
    abstract public function quote($val);

    /**
     * Connect to database
     * @param object $cfg
     * @return mixed
     */
    abstract public function connect($cfg = null);

    /**
     * Disconnect from database
     */
    public function disconnect() {}

    /**
     * Execute query
     * @param bool $returnLastId
     * @return mixed
     */
    abstract public function exec($returnLastId = false);

    /**
     * Get last inserted id
     * @return mixed
     */
    abstract public function lastId();

    /**
     * Get affected rows
     * @return int
     */
    abstract public function affectedRows();

    /**
     * Fetch single value from database
     * @return mixed
     */
    abstract public function fetchCell();

    /**
     * Fetch row as object
     * @param string $class Result type
     * @return object
     */
    abstract public function fetchObject($class = null);

    /**
     * Fetch row as array
     * @return array
     */
    abstract public function fetchArray();

    /**
     * Fetch row as associative array
     * @return array
     */
    abstract public function fetchAssoc();

    /**
     * Fetch all rows as array of objects
     * @param string $class Result type
     * @return array
     */
    abstract public function fetchAllObject($class = null);

    /**
     * Fetch all rows as array of arrays
     * @return array
     */
    abstract public function fetchAllArray();

    /**
     * Fetch all rows as array of associative arrays
     * @return array
     */
    abstract public function fetchAllAssoc();

    /**
     * Fetch first result field from all rows as array
     * @param int|string Column to fetch
     * @return array
     */
    abstract public function fetchColumn($column = 0);

    /**
     * Fetch row as object from database
     * @param string $table
     * @param string $field Field in where clause
     * @param string $value Value in where clause
     * @param string $class Result type
     * @return object
     */
    abstract public function getObject($table, $field, $value, $class = null);

    /**
     * Fetch row as array from database
     * @param string $table
     * @param string $field Field in where clause
     * @param string $value Value in where clause
     * @return array
     */
    abstract public function getArray($table, $field, $value);

    /**
     * Fetch row as associative array from database
     * @param string $table
     * @param string $field Field in where clause
     * @param string $value Value in where clause
     * @return array
     */
    abstract public function getAssoc($table, $field, $value);

    /**
     * Insert a row of data
     * @param string $table
     * @param array $row
     */
    abstract public function insertRow($table, $row);
}

