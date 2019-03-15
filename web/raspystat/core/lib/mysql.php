<?php
class MySQL
{
    //Creates a connection to the database.
    private static function createConnection()
    {
        return new mysqli(Config::$ConfigOptions['dbhost'], Config::$ConfigOptions['dbuser'], Config::$ConfigOptions['dbpass'], Config::$ConfigOptions['dbname']);
    }
    
    //Generates a query given a mysqli object, a .NET-like string template and an array of arguments.
    private static function generateQuery($db, $format, $args)
    {
        $result = trim(Utils::formatString($format, function($matches) use ($args, $db)
        {
            return $db->real_escape_string($args[$matches[1]]);
        }));
        if (substr($result, -1) != ';')
            $result .= ';';
        return $result;
    }
    
    //Executes a SQL query and returns an associative array.
    public static function executeQuery($format, $data)
    {
        $db = MySQL::createConnection();
        
        if ($db->connect_errno > 0)
            return new MySQLQueryResult(null, false, 0, '--SQL not reached.', sprintf('MySQL error: %s (1)', $db->connect_error));
        
        $sql = MySQL::generateQuery($db, $format, $data);
        
        $query = $db->query($sql);
        if (!$query) {
            return new MySQLQueryResult(null, false, 0, $sql, sprintf('MySQL error: %s (2)', $db->error));
            $db->close();
        } //!$query
        
        $rows = static::castQueryResults($query);
        
        $result = new MySQLQueryResult($rows, true, $query->num_rows, $sql, '');
        
        $query->free();
        $db->close();
        
        return $result;
    }
    
    //Executes a SQL call which does not return any data, such as an INSERT or UPDATE.
    public static function executeNonQuery($format, $data)
    {
        $db = MySQL::createConnection();
        
        if ($db->connect_errno > 0)
            return new MySQLNonQueryResult(null, false, 0, '--SQL not reached.', sprintf('MySQL error: %s (3)', $db->connect_error));
        
        $sql = MySQL::generateQuery($db, $format, $data);
        
        $query = $db->query($sql);
        if (!$query) {
            return new MySQLNonQueryResult(null, false, 0, $sql, sprintf('MySQL error: %s (4)', $db->error));
            $db->close();
        } //!$query
        
        $getID = $db->query('SELECT LAST_INSERT_ID();');
        $id    = null;
        if ($getID) {
            $row = $getID->fetch_row();
            $id  = $row[0];
        } //$getID
        
        $result = new MySQLNonQueryResult($id, true, 0, $sql, '');
        
        $getID->free();
        $db->close();
        
        return $result;
    }
    
    //Executes a SQL query resulting in a single column, single row.
    public static function executeScalar($format, $data)
    {
        $db = MySQL::createConnection();
        
        if ($db->connect_errno > 0)
            return new MySQLScalarResult(null, false, 0, '--SQL not reached.', sprintf('MySQL error: %s (5)', $db->connect_error));
        
        $sql = MySQL::generateQuery($db, $format, $data);
        
        $query = $db->query($sql);
        if (!$query) {
            return new MySQLScalarResult(null, false, 0, $sql, sprintf('MySQL error: %s (6)', $db->error));
            $db->close();
        } //!$query
        
        $rows = static::castQueryResults($query);
        $count = count($rows);
        
        $scalar = null;
        if($count > 0) {
	        $scalar = $rows[0];
	        $scalar = reset($scalar);
        }
        
        $result = new MySQLScalarResult($scalar, true, $count, $sql, '');
        
        $query->free();
        $db->close();
        
        return $result;
    }
    
    private static function castQueryResults($rs) {
	    $fields = mysqli_fetch_fields($rs);
	    $data = array();
	    $types = array();
	    foreach($fields as $field) {
	        switch($field->type) {
	            case 3:
	                $types[$field->name] = 'int';
	                break;
	            case 4:
	                $types[$field->name] = 'float';
	                break;
	            default:
	                $types[$field->name] = 'string';
	                break;
	        }
	    }
	    while($row=mysqli_fetch_assoc($rs)) array_push($data,$row);
	    for($i=0;$i<count($data);$i++) {
	        foreach($types as $name => $type) {
	            settype($data[$i][$name], $type);
	        }
	    }
	    return $data;
	}
}

//The base MySQL result class.
abstract class MySQLResult
{
    protected $count = 0;
    protected $error = '';
    protected $sql = '';
    protected $success = false;
    
    public function __construct($success = true, $count = 0, $sql = '', $error = '')
    {
        $this->count   = $count;
        $this->error   = $error;
        $this->sql     = $sql;
        $this->success = $success;
    }
    
    public function getError()
    {
        return $this->error;
    }
    
    public function getRowCount()
    {
        return $this->count;
    }
    
    public function getSQL()
    {
        return $this->sql;
    }
    
    public function successful()
    {
        return $this->success;
    }
}

//The concrete class created after an execute query call.
class MySQLQueryResult extends MySQLResult
{
    protected $rows = array();
    
    public function __construct($rows = array(), $success = true, $count = 0, $sql = '', $error = '')
    {
        parent::__construct($success, $count, $sql, $error);
        $this->rows = $rows;
    }
    
    public function getAllRows()
    {
        return $this->rows;
    }
    
    public function getRow($index)
    {
        return $this->rows[$index];
    }
}

//The concrete class created after an execute nonquery call.
class MySQLNonQueryResult extends MySQLResult
{
    protected $id = null;
    
    public function __construct($id, $success = true, $count = 0, $sql = '', $error = '')
    {
        parent::__construct($success, $count, $sql, $error);
        $this->id = $id;
    }
    
    public function getID()
    {
        return $this->id;
    }
}

//The concrete class created after an execute scalar call.
class MySQLScalarResult extends MySQLResult
{
    protected $scalar = null;
    
    public function __construct($scalar = null, $success = true, $count = 0, $sql = '', $error = '')
    {
        parent::__construct($success, $count, $sql, $error);
        $this->scalar = $scalar;
    }
    
    public function getScalar()
    {
        return $this->scalar;
    }
}
?>