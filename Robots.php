<?php namespace ZN;
/**
 * ZN PHP Web Framework
 * 
 * "Simplicity is the ultimate sophistication." ~ Da Vinci
 * 
 * @package ZN
 * @license MIT [http://opensource.org/licenses/MIT]
 * @author  Ozan UYKUN [ozan@znframework.com]
 */

class Robots
{   
    /**
     * Robots file
     * 
     * @var string
     */
    protected static $file = 'robots.txt';

    /**
     * Creates robots.txt
     */
    public static function createRobotsFile()
    {
        $rules  = Config::get('Robots', 'rules');
        $robots = '';

        if( IS::array($rules) ) foreach( $rules as $key => $val )
        {
            # Single usage
            if( ! is_numeric($key) )
            {
                self::createRobotsFileContent($key, $val, $robots);
            }
            # Multi usage
            else
            {
                if( IS::array($val) ) foreach( $val as $r => $v ) 
                {
                    self::createRobotsFileContent($r, $v, $robots);
                }
            }
        }

        # If the content to be written is the same, rewriting is not performed.
        if( trim($robots) === self::getRobotsContent() )
        {
            return false;
        }

        # The contents of the robot file are created.
        if( ! self::putRobotsContent($robots) )
        {
            throw new Exception('Error', 'fileNotWrite', self::$file);
        }
    }

    /**
     * Protected put robots content
     */
    protected static function putRobotsContent($robots)
    {
        return file_put_contents(self::$file, trim($robots));
    }

    /**
     * Protected get robots content
     */
    protected static function getRobotsContent()
    {
        if( is_file(self::$file) )
        {
           return trim(file_get_contents(self::$file));
        }
        
        return '';
    }

    /**
     * Protected create robots file content
     */
    protected static function createRobotsFileContent($key, $val, &$robots)
    {
        switch( $key )
        {
            case 'userAgent':
                $robots .= ! empty( $val ) ? 'User-agent: '.$val.EOL : '';
            break;

            case 'allow'    :
            case 'disallow' :
                if( ! empty($val) ) foreach( $val as $v )
                {
                    $robots .= ucfirst($key).': '.$v.EOL;
                }
            break;
        }
    }
}