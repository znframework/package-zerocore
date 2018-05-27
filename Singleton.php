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

class Singleton
{
    /**
     * singleton
     * 
     * @var self
     * 
     * @return self
     */
    protected static $singleton = NULL;
    
    /**
     * singleton
     * 
     * 5.7.4.4[changed]
     * 5.7.4.5[changed]
     * 
     * @param string $class
     * 
     * @return self
     */
    public static function class(String $class)
    {
        if( ! isset(self::$singleton[$class]) ) 
        {
            self::$singleton[$class] = new $class;
        }

        return self::$singleton[$class];
    }
}