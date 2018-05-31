<?php namespace ZN\ErrorHandling;
/**
 * ZN PHP Web Framework
 * 
 * "Simplicity is the ultimate sophistication." ~ Da Vinci
 * 
 * @package ZN
 * @license MIT [http://opensource.org/licenses/MIT]
 * @author  Ozan UYKUN [ozan@znframework.com]
 */

use ZN\Lang;
use ZN\Config;
use ZN\Helper;
use ZN\Datatype;
use ZN\Inclusion;

class Exceptions extends \Exception implements ExceptionsInterface
{   
    /**
     * Error codes
     * 
     * @var array
     */
    public static $errorCodes = 
    [
        0       => 'ERROR',
        2       => 'WARNING',
        4       => 'PARSE',
        8       => 'NOTICE',
        16      => 'CORE_ERROR',
        32      => 'CORE_WARNING',
        64      => 'COMPILE_ERROR',
        128     => 'COMPILE_WARNING',
        256     => 'USER_ERROR',
        512     => 'USER_WARNING',
        1024    => 'USER_NOTICE',
        2048    => 'STRICT',
        4096    => 'RECOVERABLE_ERROR',
        8192    => 'DEPRECATED',
        16384   => 'USER_DEPRECATED',
        32767   => 'ALL'
    ];

    /**
     * Magic to string 
     * 
     * @param void
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->_template($this->getMessage(), $this->getFile(), $this->getLine(), $this->getTrace());
    }

    /**
     * Throw exception
     * 
     * @param string $message = NULL
     * @param string $key     = NULL
     * @param mixed  $send    = NULL
     * 
     * @return void
     */
    public static function throws(String $message = NULL, String $key = NULL, $send = NULL)
    {
        $debug = self::_throwFinder(debug_backtrace(2), 0, 2);

        if( $lang = Lang::select($message, $key, $send) )
        {
            $message = '['.self::_cleanClassName($debug['class']).'::'.$debug['function'].'()] '.$lang;
        }

        self::table('self', $message, $debug['file'], $debug['line']);
    }

    /**
     * Get exception table
     * 
     * @param mixed  $no    = NULL
     * @param string $msg   = NULL
     * @param string $file  = NULL
     * @param string $line  = NULL
     * @param array  $trace = NULL
     * 
     * @return void
     */
    public static function table($no = NULL, String $msg = NULL, String $file = NULL, String $line = NULL, Array $trace = NULL)
    {
        if( is_object($no) )
        {
            $msg   = $no->getMessage();
            $file  = $no->getFile();
            $line  = $no->getLine();
            $trace = $no->getTrace(); 
            
            $no    = 'NULL';
        }

        $lang    = Lang::select('Templates');
        $message = $lang['line'].':'.$line.', '.$lang['file'].':'.$file.', '.$lang['message'].':'.$msg;

        Helper::report('ExceptionError', $message, 'ExceptionError');

        $table = self::_template($msg, $file, $line, $no, $trace);

        $projectError = Config::get('Project');

        if
        ( 
            in_array($no, $projectError['exitErrors'] ?? [], true) || 
            in_array(self::$errorCodes[$no] ?? NULL, $projectError['exitErrors'] ?? [], true) 
        )
        {
            exit($table);
        }

        echo $table;
    }

    /**
     * Continue exception
     * 
     * @param string $msg
     * @param string $file
     * @param string $line
     * 
     * @return string
     */
    public static function continue($msg, $file, $line)
    {
        return self::_template($msg, $file, $line, NULL, NULL);
    }

    /**
     * Restore exception
     * 
     * @param void
     * 
     * @return bool
     */
    public static function restore() : Bool
    {
        return restore_exception_handler();
    }

    /**
     * Set exception handler
     * 
     * @param void
     * 
     * @return void
     */
    public static function handler()
    {
        set_exception_handler([__CLASS__, 'table']);
    }

    /**
     * protected exception template
     * 
     * @param string $msg
     * @param string $file
     * @param string $line
     * @param string $no
     * @param array  $trace
     * 
     * @return string
     */
    private static function _template($msg, $file, $line, $no, $trace)
    {
        $projects = Config::get('Project');

        if( ! $projects['errorReporting'] )
        {
            return false;
        }

        if( in_array($no, $projects['escapeErrors'], true) || in_array(self::$errorCodes[$no] ?? NULL, $projects['escapeErrors'], true) )
        {
            return false;
        }

        if( self::_returnValue($msg) === true )
        {
            return false;
        }
        
        $exceptionData =
        [
            'type'    => self::$errorCodes[$no] ?? 'ERROR',
            'message' => $msg,
            'file'    => $file,
            'line'    => $line,
            'trace'   => $trace
        ];
        
        if( stristr($exceptionData['file'] ?? $file, DS . 'Buffering.php') )
        {
            $templateWizardData    = self::_templateWizard();
            $exceptionData['file'] = $templateWizardData->file;

            if( empty($exceptionData['message']) )
            {
                $exceptionData['message'] = $templateWizardData->message;
            }
        }

        ob_end_clean();

        return Inclusion\View::use('Table', $exceptionData, true, __DIR__ . '/Resources/');
    }

    /**
     * protected clean class name
     * 
     * @param string $class
     * 
     * @return string
     */
    protected static function _cleanClassName($class)
    {
        return str_ireplace(INTERNAL_ACCESS, '', Datatype::divide($class, '\\', -1));
    }

    /**
     * Trace finder
     * 
     * @param array $trace
     * @param int   $p1 = 2
     * @param int   $p2 = 0
     * 
     * @return array
     */
    protected static function _traceFinder($trace, $p1 = 2, $p2 = 0)
    {
        if
        (
            isset($trace[$p1]['class']) &&
            self::_cleanClassName($trace[$p1]['class']) === 'StaticAccess' &&
            $trace[$p1]['function'] === '__callStatic'
        )
        {
            $traceInfo = $trace[$p1];

            $traceInfo['class']    = $trace[$p2]['class']    ?? $trace[$p1]['class'];
            $traceInfo['function'] = $trace[$p2]['function'] ?? $trace[$p1]['function'];
        }
        else
        {
            $traceInfo = $trace[$p2] ?? self::_traceFinder(debug_backtrace(2), 8, 6);
        }

        if( ! isset($traceInfo['class']) )
        {
            $traceInfo['class'] = $traceInfo['function'];
        }

        return
        [
            'class'    => self::_cleanClassName($traceInfo['class']),
            'function' => $traceInfo['function'],
            'file'     => $traceInfo['file'],
            'line'     => $traceInfo['line'],
            'trace'    => $trace
        ];
    }

    /**
     * Throw finder
     * 
     * @param array $trace
     * @param int   $p1 = 2
     * @param int   $p2 = 0
     * 
     * @return array
     */
    protected static function _throwFinder($trace, $p1 = 3, $p2 = 5)
    {
        $classInfo = $trace[$p1];
        $fileInfo  = $trace[$p2];

        if( ! isset($classInfo['class']) && isset($classInfo['function']) )
        {
            $classInfo['class'] = $classInfo['function'];
            $fileInfo['file']   = $classInfo['file'];
            $fileInfo['line']   = $classInfo['line'];
        }

        return
        [
            'class'    => self::_cleanClassName($classInfo['class']),
            'function' => $classInfo['function'],
            'file'     => $fileInfo['file'],
            'line'     => $fileInfo['line'],
            'trace'    => $trace
        ];
    }

    /**
     * protected return value
     * 
     * @param string $msg
     * 
     * @return bool
     */
    protected static function _returnValue($msg)
    {
        if( stripos($msg, 'Return value') === 0 )
        {
            return true;
        }

        return false;
    }

    /**
     * Handle template wizard
     * 
     * @param void
     * 
     * @return object
     */
    protected static function _templateWizard()
    {
        $trace = debug_backtrace()[6]['args']    ?? [NULL];
        $args  = debug_backtrace()[1]['args'][4] ?? [];

        foreach( $args as $key => $value )
        {
            if( is_array($value) && preg_match('/Views\/.*?\.\wizard\.php/', $find = ($value['args'][0] ?? NULL)) )
            {
                $file = $find;
            }
        }

        $file    = $file ?? VIEWS_DIR.($trace[0] ?? strtolower(CURRENT_CFUNCTION).'.wizard') . '.php';
        $message = $trace[0] ?? Lang::select('Error', 'templateWizard');

        $exceptionData['file']    = $file;
        $exceptionData['message'] = $message;

        return (object) $exceptionData;
    }

    /**
     * Display exception table
     * 
     * @param string $file
     * @param string $line
     * @param string $key
     * 
     * @return void
     */
    public static function display($file, $line, $key)
    {
        ?>
        <a href="#openExceptionMessage<?php echo $key?>" class="list-group-item panel-header" style="color:#999;" data-toggle="collapse">
            <span><i class="fa fa-angle-down fa-fw panel-text"></i>&nbsp;&nbsp;&nbsp;&nbsp;
            <?php echo $file ?? NULL; ?></span>
        </a>
        <div id="openExceptionMessage<?php echo $key?>" class="collapse<?php echo $key !== NULL ? '' : ' in'?>">
        <pre style="color:#ccc; background:#222; margin-top:-20px; border:0px">
        <?php
        $content = file($file);
        $newdata = '<?PHP' . EOL;
        $intline = $line;
        $errorBlock = '<div class="error-block col-lg-12"></div>';

        for( $i = (($startLine = ($intline - 10)) < 0 ? 0 : $startLine); $i < ($intcount = $intline + 10); $i++ )
        {
            if( ! isset($content[$i]) )
            {
                break;
            }

            $index = $i + 1;
            $line  = $content[$i];

            if( $index == $intline )
            {
                $problem = ' {!!!!}';
                
            }
            else
            {
                $problem = ' ';
            }
            
            $newdata .= $index.'.' . $problem .
            str_repeat(' ', strlen($intcount) - strlen($i + 1)) . 
            $line;
        }
        
        echo preg_replace('/(<br\s\/>|'.CRLF.'|'.CR.'|'.LF.')+/', EOL, str_replace(['&#60;&#63;PHP', '{!!!!}'], [NULL, $errorBlock], Helper::highlight($newdata, 
        [
            'default:color' => '#ccc',
            'keyword:color' => '#00BFFF',
            'string:color'  => '#fff'
        ])));
        ?></pre></div><?php
    }
}