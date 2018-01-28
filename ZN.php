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

use ZN\Services\Restful;
use ZN\Protection\Separator;
use ZN\ErrorHandling\Exceptions;

class ZN
{
    /**
     * Keeps custom defines
     * 
     * @var array
     */
    protected static $defines = [];

    /**
     * Magic call static
     * 
     * @param string $class
     * @param array  $parameters
     * 
     * @return mixed
     */
    public static function __callStatic($class, $parameters)
    {
        return Singleton::class($class, $parameters);
    }

    /**
     * Custom Defines
     * 
     * @param array $defines
     * 
     * @return self
     */
    public static function defines(Array $defines)
    {
        self::$defines = $defines;

        return new self;
    }

    /**
     * Run ZN
     * 
     * @param string $type     = 'EIP' - options[EIP|SE]
     * @param string $version  = '5.6.0'
     * @param string $dedicate = 'Nikola Tesla'
     * 
     * @return void|false
     */
    public static function run(String $type = 'EIP', String $version = '5.6.0', String $dedicate = 'Nikola Tesla')
    {
        # PHP shows code errors.
        ini_set('display_errors', true);
        
        # The system starts the load time.
        define('START_BENCHMARK', microtime(true));

        # ZN Version
        define('ZN_VERSION', $version);

        # Dedicated
        define('ZN_DEDICATE', $dedicate);

        # It shows you which framework you are using. SE for single edition, EIP for multi edition.
        define('PROJECT_TYPE', $type);

        # Define standart constants
        self::predefinedConstants();

        # Predefined Functions
        self::predefinedFunctions();

        # Enables class loading by automatically activating the object call.
        Autoloader::register();

        # Defines constants required for system and user.
        Autoloader::defines();

        # Provides data about the current working url.
        Structure::defines();

        # If the operation is executed via console, the code flow is not continue.  
        if( defined('CONSOLE_ENABLED') )
        {
            return false;
        }

        # The code to be written to this layer runs before the system files are 
        # loaded. For this reason, you can not use ZN libraries.
        Base::layer('Top');

        # Enables route filters.
        Singleton::class('ZN\Routing\Route')->filter();

        # You can use system constants and libraries in this layer since the code 
        # to write to this layer is used immediately after the auto loader. 
        # All Config files can be configured on this layer since this layer runs 
        # immediately after the auto installer.
        Base::layer('TopBottom');

        # Run Kernel
        try 
        { 
            Kernel::run();  
        }
        catch( Throwable $e )
        {
            if( PROJECT_MODE !== 'publication' ) 
            {
                Exceptions::table($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace());
            }   
        }

        # The system finishes the load time.
        define('FINISH_BENCHMARK', microtime(true));

        # Creates a table that calculates the operating performance of the system. 
        # To open this table, follow the steps below.
        In::benchmarkReport();
    }

    /**
     * Protected Predefined Functions
     */
    protected static function predefinedFunctions()
    {
        require __DIR__ . '/Functions.php';
    }

    /**
     * Protected Predefined Constants
     */
    protected static function predefinedConstants()
    {
        # Defined Standart Constants
        define('REQUIRED_PHP_VERSION', '7.0.0');
        define('SSL_STATUS', (($_SERVER['HTTPS'] ?? NULL) === 'on' ? 'https' : 'http') . '://');
        define('PROJECT_CONTROLLER_NAMESPACE', 'Project\Controllers\\');
        define('PROJECT_COMMANDS_NAMESPACE' , 'Project\Commands\\');
        define('EXTERNAL_COMMANDS_NAMESPACE', 'External\Commands\\');
        define('INTERNAL_ACCESS', 'Internal');
        define('EOL', PHP_EOL);
        define('CRLF', "\r\n" );
        define('CR', "\r");
        define('LF', "\n");
        define('HT', "\t");
        define('TAB', "\t");
        define('FF', "\f");
        define('DS', DIRECTORY_SEPARATOR);

        # All project types
        define('PROJECT_TYPE_DIRS', 
        [
            'SE' => 
            [
                'INTERNAL_DIR'    => 'Libraries/',
                'EXTERNAL_DIR'    => NULL,
                'SETTINGS_DIR'    => 'Config/',
                'DIRECTORY_INDEX' => 'zeroneed.php',
                'CONTROLLERS_DIR' => 'Controllers/',
                'VIEWS_DIR'       => 'Views/',
                'ROUTES_DIR'      => 'Routes/',
                'CONFIG_DIR'      => 'Config/',
                'DATABASES_DIR'   => 'Databases/',
                'STORAGE_DIR'     => 'Storage/',
                'COMMANDS_DIR'    => 'Commands/',
                'LANGUAGES_DIR'   => 'Languages/',
                'LIBRARIES_DIR'   => 'Libraries/',
                'MODELS_DIR'      => 'Models',
                'STARTING_DIR'    => 'Starting/',
                'AUTOLOAD_DIR'    => 'Starting/Autoload/',
                'HANDLOAD_DIR'    => 'Starting/Handload/',
                'LAYERS_DIR'      => 'Starting/Layers/',
                'RESOURCES_DIR'   => 'Resources/',
                'FILES_DIR'       => 'Resources/Files/',
                'TEMPLATES_DIR'   => 'Resources/Templates/',
                'THEMES_DIR'      => 'Resources/Themes/',
                'PLUGINS_DIR'     => 'Resources/Plugins/',
                'UPLOADS_DIR'     => 'Resources/Uploads/'
            ],

            'EIP' => 
            [
                'INTERNAL_DIR'    => 'Internal/',
                'EXTERNAL_DIR'    => 'External/',
                'SETTINGS_DIR'    => 'Settings/',
                'DIRECTORY_INDEX' => 'zeroneed.php',
                'CONTROLLERS_DIR' => 'Controllers/',
                'VIEWS_DIR'       => 'Views/',
                'ROUTES_DIR'      => 'Routes/',
                'CONFIG_DIR'      => 'Config/',
                'DATABASES_DIR'   => 'Databases/',
                'STORAGE_DIR'     => 'Storage/',
                'COMMANDS_DIR'    => 'Commands/',
                'LANGUAGES_DIR'   => 'Languages/',
                'LIBRARIES_DIR'   => 'Libraries/',
                'MODELS_DIR'      => 'Models/',
                'STARTING_DIR'    => 'Starting/',
                'AUTOLOAD_DIR'    => 'Starting/Autoload/',
                'HANDLOAD_DIR'    => 'Starting/Handload/',
                'LAYERS_DIR'      => 'Starting/Layers/',
                'RESOURCES_DIR'   => 'Resources/',
                'FILES_DIR'       => 'Resources/Files/',
                'TEMPLATES_DIR'   => 'Resources/Templates/',
                'THEMES_DIR'      => 'Resources/Themes/',
                'PLUGINS_DIR'     => 'Resources/Plugins/',
                'UPLOADS_DIR'     => 'Resources/Uploads/'
            ],

            'CE' => 
            [
                'INTERNAL_DIR'    => self::$defines['INTERNAL_DIR']    ?? NULL,
                'EXTERNAL_DIR'    => NULL,
                'SETTINGS_DIR'    => self::$defines['SETTINGS_DIR']    ?? NULL,
                'DIRECTORY_INDEX' => self::$defines['DIRECTORY_INDEX'] ?? 'index.php',
                'CONTROLLERS_DIR' => self::$defines['CONTROLLERS_DIR'] ?? NULL,
                'VIEWS_DIR'       => self::$defines['VIEWS_DIR']       ?? NULL,
                'ROUTES_DIR'      => self::$defines['ROUTES_DIR']      ?? NULL,
                'CONFIG_DIR'      => self::$defines['CONFIG_DIR']      ?? NULL,
                'DATABASES_DIR'   => self::$defines['DATABASES_DIR']   ?? NULL,
                'STORAGE_DIR'     => self::$defines['STORAGE_DIR']     ?? NULL,
                'COMMANDS_DIR'    => self::$defines['COMMANDS_DIR']    ?? NULL,
                'LANGUAGES_DIR'   => self::$defines['LANGUAGES_DIR']   ?? NULL,
                'LIBRARIES_DIR'   => self::$defines['LIBRARIES_DIR']   ?? NULL,
                'MODELS_DIR'      => self::$defines['MODELS_DIR']      ?? NULL,
                'STARTING_DIR'    => self::$defines['STARTING_DIR']    ?? NULL,
                'AUTOLOAD_DIR'    => self::$defines['AUTOLOAD_DIR']    ?? NULL,
                'HANDLOAD_DIR'    => self::$defines['HANDLOAD_DIR']    ?? NULL,
                'LAYERS_DIR'      => self::$defines['LAYERS_DIR']      ?? NULL,
                'RESOURCES_DIR'   => self::$defines['RESOURCES_DIR']   ?? NULL,
                'FILES_DIR'       => self::$defines['FILES_DIR']       ?? NULL,
                'TEMPLATES_DIR'   => self::$defines['TEMPLATES_DIR']   ?? NULL,
                'THEMES_DIR'      => self::$defines['THEMES_DIR']      ?? NULL,
                'PLUGINS_DIR'     => self::$defines['PLUGINS_DIR']     ?? NULL,
                'UPLOADS_DIR'     => self::$defines['UPLOADS_DIR']     ?? NULL,
            ]
        ]);

        # Get project dirs
        define('GET_DIRS', PROJECT_TYPE_DIRS[PROJECT_TYPE]);

        # The system directory is determined according to ZN project type.
        define('INTERNAL_DIR', GET_DIRS['INTERNAL_DIR']);
        define('EXTERNAL_DIR', GET_DIRS['EXTERNAL_DIR']);
        define('SETTINGS_DIR', GET_DIRS['SETTINGS_DIR']);
        define('PROJECTS_DIR', 'Projects/');

        # Directory Index
        define('DIRECTORY_INDEX', GET_DIRS['DIRECTORY_INDEX']);
        define('BASE_DIR', ltrim(explode(DIRECTORY_INDEX, $_SERVER['SCRIPT_NAME'])[0], '/'));

        # It keeps path of the files needed for the system.
        define('ZEROCORE', INTERNAL_DIR . 'ZN/');

        # The system gives the knowledge of the actual root directory.
        define('REAL_BASE_DIR', pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_DIRNAME) . '/');
    }
}
