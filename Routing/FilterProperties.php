<?php namespace ZN\Routing;
/**
 * ZN PHP Web Framework
 * 
 * "Simplicity is the ultimate sophistication." ~ Da Vinci
 * 
 * @package ZN
 * @license MIT [http://opensource.org/licenses/MIT]
 * @author  Ozan UYKUN [ozan@znframework.com]
 */

class FilterProperties
{
    /**
     * Keeps Filters
     * 
     * @var array
     */
    protected $filters = [];

    /**
     * Restore
     * 
     * @param string|array $ips
     * @param string       $uri = NULL
     * 
     * @return Route
     */
    public function restore($ips, string $uri = NULL)
    {
        $this->filters['restore']['ips'] = (array) $ips;
        $this->filters['restore']['uri'] = $uri;

        return $this;
    }

    /**
     * CSRF
     * 
     * @param bool $usable = true
     * 
     * @return Route
     */
    public function usable(bool $usable = true)
    {
        $this->filters['usable'] = $usable;

        return $this;
    }

    /**
     * CSRF
     * 
     * @param string $uri = 'post'
     * 
     * @return Route
     */
    public function CSRF(string $uri = 'post')
    {
        $this->filters['csrf'] = $uri;

        return $this;
    }

    /**
     * Ajax
     * 
     * @return Route
     */
    public function ajax()
    {
        $this->filters['ajax'] = true;

        return $this;
    }

    /**
     * Callback
     * 
     * @param callable $callback
     * 
     * @return Route
     */
    public function callback(callable $callback)
    {
        $this->filters['callback'] = $callback;

        return $this;
    }

    /**
     * Sets methods
     * 
     * @param string ...$methods
     * 
     * @return Route
     */
    public function method(string ...$methods)
    {
        $this->filters['method'] = $methods;

        return $this;
    }

    /**
     * Sets redirect
     * 
     * @param string $redirect
     * 
     * @return Route
     */
    public function redirect(string $redirect)
    {
        $this->filters['redirect'] = $redirect;

        return $this;
    }
}
