<?php
  // Class providing generic data access functionality

// Added For Error Handling Section
// These should be true while developing the web site
define('DEBUGGING', isset($_GET['trace']));
ini_set('display_errors', (DEBUGGING)? 'on' : 'off');
define('IS_WARNING_FATAL', DEBUGGING);
// The error types to be reported
define('ERROR_TYPES', E_ALL);
// Settings about mailing the error messages to admin
define('SEND_ERROR_MAIL', false);
define('ADMIN_ERROR_MAIL', 'eng.keramati@gmail.com');
define('SENDMAIL_FROM', 'errors@akord.ir');
ini_set('sendmail_from', SENDMAIL_FROM);

define('LOG_ERRORS', false);
define('SITE_ROOT', dirname(__FILE__, 2));
//define('LOG_ERRORS_FILE', 'c:\\admin.txt'); // Windows
define('LOG_ERRORS_FILE', SITE_ROOT . '/errors.log'); // Linux
/* Generic error message to be displayed instead of debug info
(when DEBUGGING is false) */
define('SITE_GENERIC_ERROR_MESSAGE', '<h1>کاربر گرامی، مشکلی در ارتباط با سایت  پیش آمده است</h1>');

  class DB
  {
    // Hold an instance of the PDO class
    private static $_mHandler;

    // Private constructor to prevent direct creation of object
    private function __construct()
    {
    }     

    private static function convertPersianWord($item)
    {
      return str_replace(array('ي', 'ك'), array('ی', 'ک'), $item);
    }
    private static function convertPersianWords($arrayParams)
    {
      foreach($arrayParams as $key => $value)
      {
        $arrayParams[$key] = self::convertPersianWord($value);
      }
      return $arrayParams; 
    }
    // Return an initialized database handler 
    private static function GetHandler()
    {
      // Create a database connection only if one doesn?t already exist
      if (!isset(self::$_mHandler))
      {
        // Execute code catching potential exceptions
        try
        {
          // Create a new PDO class instance
          self::$_mHandler =
          new PDO(PDO_DSN, DB_USERNAME, DB_PASSWORD,
            array(PDO::ATTR_PERSISTENT => DB_PERSISTENCY, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));

          // Configure PDO to throw exceptions
          self::$_mHandler->setAttribute(PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION);
        }
        catch (PDOException $e)
        {
          // Close the database handler and trigger an error
          self::Close();
          trigger_error($e->getMessage(), E_USER_ERROR);
        }
      }

      // Return the database handler
      return self::$_mHandler;
    }


    // Clear the PDO class instance
    public static function Close()
    {
      self::$_mHandler = null;
    }

    // Wrapper method for PDOStatement::execute()
    public static function Execute($sqlQuery, $params = null)
    {
      $params = SELF::convertPersianWords($params);
      // Try to execute an SQL query or a stored procedure
      try
      {
        // Get the database handler
        $database_handler = self::GetHandler();

        // Prepare the query for execution
        $statement_handler = $database_handler->prepare('SET NAMES UTF8;' . $sqlQuery);

        // Execute query
             
        $execResult = $statement_handler->execute($params);
        //print_r($params);
        self::Close();
        if($execResult == 1) return true; return false;
        //return $execResult;
      }
      // Trigger an error if an exception was thrown when executing the SQL query
      catch(PDOException $e)
      {
        // Close the database handler and trigger an error
        self::Close();
        trigger_error($e->getMessage(), E_USER_ERROR);
      }
    }

    // Wrapper method for PDOStatement::fetchAll()
    public static function GetAll($sqlQuery, $params = null,
      $fetchStyle = PDO::FETCH_ASSOC)
    {
      // Initialize the return value to null
      $result = null;

      // Try to execute an SQL query or a stored procedure
      try
      {
        // Get the database handler
        $database_handler = self::GetHandler();

        // Prepare the query for execution
        //
        //print_r($sqlQuery);
        $statement_handler = $database_handler->prepare($sqlQuery);

        // Execute the query
        $statement_handler->execute($params);
        //print_r($params);
        // Fetch result
        $result = $statement_handler->fetchAll($fetchStyle);
      }
      // Trigger an error if an exception was thrown when executing the SQL query
      catch(PDOException $e)
      {
        // Close the database handler and trigger an error
        self::Close();
        trigger_error($e->getMessage(), E_USER_ERROR);
      }

      // Return the query results
      return $result;
    }

    


    // Wrapper method for PDOStatement::fetch()
    public static function GetRow($sqlQuery, $params = null,
      $fetchStyle = PDO::FETCH_ASSOC)
    {
      // Initialize the return value to null
      $result = null;

      // Try to execute an SQL query or a stored procedure
      try
      {
        // Get the database handler
        $database_handler = self::GetHandler();

        // Prepare the query for execution
        $statement_handler = $database_handler->prepare($sqlQuery);

        // Execute the query
        $statement_handler->execute($params);

        // Fetch result
        $result = $statement_handler->fetch($fetchStyle);
      }
      // Trigger an error if an exception was thrown when executing the SQL query
      catch(PDOException $e)
      {
        // Close the database handler and trigger an error
        self::Close();
        trigger_error($e->getMessage(), E_USER_ERROR);
      }

      // Return the query results
      return $result;
    }

    // Return the first column value from a row
    public static function GetOne($sqlQuery, $params = null)
    {
      // Initialize the return value to null    
      $result = null;

      // Try to execute an SQL query or a stored procedure
      try
      {
        // Get the database handler
        $database_handler = self::GetHandler();

        // Prepare the query for execution
        $statement_handler = $database_handler->prepare($sqlQuery);

        // Execute the query
        $statement_handler->execute($params);

        // Fetch result
        $result = $statement_handler->fetch(PDO::FETCH_NUM);

        /* Save the first value of the result set (first column of the first row)
        to $result */
        $result = $result[0];
      }
      // Trigger an error if an exception was thrown when executing the SQL query
      catch(PDOException $e)
      {
        // Close the database handler and trigger an error
        self::Close();
        trigger_error($e->getMessage(), E_USER_ERROR);
      }

      // Return the query results
      return $result;
    }
  }



class ErrorHandler
{
  // Private constructor to prevent direct creation of object
  private function __construct()
  {
  }
  /* Set user error-handler method to ErrorHandler::Handler method */
  public static function SetHandler($errTypes = ERROR_TYPES)
  {
    return set_error_handler(array ('ErrorHandler', 'Handler'), $errTypes);
  }
  // Error handler method
  public static function Handler($errNo, $errStr, $errFile, $errLine)
  {
    /* The first two elements of the backtrace array are irrelevant:
    - ErrorHandler.GetBacktrace
    - ErrorHandler.Handler */
    $backtrace = ErrorHandler::GetBacktrace(2);
    // Error message to be displayed, logged, or mailed
    $error_message = "\nERRNO: $errNo\nTEXT: $errStr" .
        "\nmahale khata: $errFile, dar khate  " .
        "$errLine, at " . date('F j, Y, g:i a') .
        "\nShowing backtrace:\n$backtrace\n\n";
    // Email the error details, in case SEND_ERROR_MAIL is true
    if (SEND_ERROR_MAIL == true)
      error_log($error_message, 1, ADMIN_ERROR_MAIL, "From: " .
          SENDMAIL_FROM . "\r\nTo: " . ADMIN_ERROR_MAIL);
    // Log the error, in case LOG_ERRORS is true
    if (LOG_ERRORS == true)
      error_log($error_message, 3, LOG_ERRORS_FILE);
    /* Warnings don't abort execution if IS_WARNING_FATAL is false
    E_NOTICE and E_USER_NOTICE errors don't abort execution */
    if (($errNo == E_WARNING && IS_WARNING_FATAL == false) ||
        ($errNo == E_NOTICE || $errNo == E_USER_NOTICE))
      // If the error is nonfatal ...
    {
      // Show message only if DEBUGGING is true
      if (DEBUGGING == true)
        echo '<div class="error_box"><pre>' . $error_message . '</pre></div>';
    }
    else
      // If error is fatal ...
    {
      // Show error message
      if (DEBUGGING == true)
        echo '<div class="error_box"><pre>'. $error_message . '</pre></div>';
      else
        echo SITE_GENERIC_ERROR_MESSAGE;
      // Stop processing the request
      exit();
    }
  }
  // Builds backtrace message
  public static function GetBacktrace($irrelevantFirstEntries)
  {
    $s = '';
    $MAXSTRLEN = 64;
    $trace_array = debug_backtrace();
    for ($i = 0; $i < $irrelevantFirstEntries; $i++)
      array_shift($trace_array);
    $tabs = sizeof($trace_array) - 1;
    foreach ($trace_array as $arr)
    {
      $tabs -= 1;
      if (isset ($arr['class']))
        $s .= $arr['class'] . '.';
      $args = array ();
      if (!empty ($arr['args']))
        foreach ($arr['args']as $v)
        {
          if (is_null($v))
            $args[] = 'null';
          elseif (is_array($v))
            $args[] = 'Array[' . sizeof($v) . ']';
          elseif (is_object($v))
            $args[] = 'Object: ' . get_class($v);
          elseif (is_bool($v))
            $args[] = $v ? 'true' : 'false';
          else
          {
            $v = (string)@$v;
            $str = htmlspecialchars(substr($v, 0, $MAXSTRLEN));
            if (strlen($v) > $MAXSTRLEN)
              $str .= '...';
            $args[] = '"' . $str . '"';
          }
        }
      $s .= $arr['function'] . '(' . implode(', ', $args) . ')';
      $line = (isset ($arr['line']) ? $arr['line']: 'unknown');
      $file = (isset ($arr['file']) ? $arr['file']: 'unknown');
      $s .= sprintf(' # line %4d, file: %s', $line, $file);
      $s .= "\n";
    }
    return $s;
  }
}
ErrorHandler::SetHandler(ERROR_TYPES);


?>