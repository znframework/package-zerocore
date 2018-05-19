<?php namespace ZN\Tests\Inclusion;
/**
 * ZN PHP Web Framework
 * 
 * "Simplicity is the ultimate sophistication." ~ Da Vinci
 * 
 * @package ZN
 * @license MIT [http://opensource.org/licenses/MIT]
 * @author  Ozan UYKUN [ozan@znframework.com]
 */

use ZN\Controller\UnitTest;

class View extends UnitTest
{
    const unit =
    [
        'class'   => 'ZN\Inclusion\View',
        'methods' => 
        [
            'container' => ['example'],
            'use'       => ['example', [], true]
        ]
    ];
}
