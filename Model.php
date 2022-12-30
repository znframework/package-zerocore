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

#[\AllowDynamicProperties]

class Model
{
    /**
     * Magic get
     * 
     * @param string $class
     * 
     * @return mixed
     */
    public function __get($class)
    {
        if( ! isset($this->$class) )
        {
            $this->$class = Singleton::class($class);  
        }

        return $this->$class;
    }
}
