<?php namespace ZN\Ability;
/**
 * ZN PHP Web Framework
 * 
 * "Simplicity is the ultimate sophistication." ~ Da Vinci
 * 
 * @package ZN
 * @license MIT [http://opensource.org/licenses/MIT]
 * @author  Ozan UYKUN [ozan@znframework.com]
 */

use ZN\Singleton;

trait UnitTest
{
    /**
     * Protected unit methods
     * 
     * @var array
     */
    protected static $unitMethods = [];

    /**
     * Protected compares
     * 
     * @var array
     */
    protected static $compares = [];

    /**
     * Protected parameters
     * 
     * @var array
     */
    protected static $parameters    = [];

    /**
     * Magic call
     * 
     * @param string $method
     * @param array  $parameters
     */
    public function __call($method, $parameters)
    {
        $class = self::unitClass();

        self::$parameters[] = $parameters;

        return $class::$method(...$parameters);
    }

    /**
     * Get result
     * 
     * @param string ...$method
     * 
     * @return string
     */
    public static function result(...$method)
    {
        if( $list = self::getCalledMethodList() )
        {
            $callClass = get_called_class();
            
            foreach( $list as $key => $met )
            {
                (new $callClass)->$met();

                $met = self::convertMultipleMethodName($met);

                self::$unitMethods[ltrim($met, '_')] = self::$parameters[$key];
            }
        }

        $class   = self::unitClass();
        $methods = self::unitMethods();

        if( ! empty($method) )
        {
            $oldMethods = $methods;
            $methods    = [];

            foreach( $method as $met )
            {
                $methods[$met] = $oldMethods[$met];
            }
        }

        $tester = Singleton::class('ZN\Helpers\Tester');
   
        $tester->class($class)
               ->methods($methods)
               ->compares(self::$compares)
               ->start();

        return $tester->result();
    }

    /**
     * Protected unit class
     */
    protected static function unitClass()
    {
        if( defined('static::unit') )
        {
            $class = static::unit['class'] ?? NULL;
        }

        return $class ?? str_replace('\\Tests\\', '\\', get_called_class());
    }

    /**
     * Protected unit methods
     */
    protected static function unitMethods()
    {
        $methods = [];

        if( defined('static::unit') )
        {
            $methods = static::unit['methods'];
        }

        return $methods + self::$unitMethods;
    }

    /**
     * Protected get called method list
     */
    protected static function getCalledMethodList()
    {
        $currentMethods = get_class_methods(__CLASS__);

        $methods = get_class_methods(get_called_class());

        return array_diff($methods, $currentMethods);
    }

    /**
     * Protected compare
     */
    protected function compare($first, $second)
    {
        $method = ltrim(debug_backtrace()[1]['function'], '_');

        $method = self::convertMultipleMethodName($method);
        
        self::$compares[$method] = $first === $second;
    }
    
    /**
     * Protected convert multiple method name
     */
    protected static function convertMultipleMethodName($name)
    {
        if( preg_match('/(\w+)([0-9]+)/', $name, $match) )
        {
            return $match[1] . ':' . $match[2] ;
        }

        return $name; 
    }
}
