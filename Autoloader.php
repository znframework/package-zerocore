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

class Autoloader
{
    /**
     * Keep classes
     * 
     * @var array
     */
    protected static $classes;

    /**
     * Keep namespaces
     * 
     * @var array
     */
    protected static $namespaces;

    /**
     * Keep classmap path
     * 
     * @var string
     */
    protected static $path = PROJECT_DIR . 'ClassMap.php';

    /**
     * Keep static access directory
     * 
     * @var string
     */
    protected static $staticAccessDirectory = RESOURCES_DIR . 'Statics/';

    /**
     * Starts the class load process.
     * 
     * @param string $class
     * 
     * @return void
     */
    public static function run(String $class)
    {
        # Automatically loads internal facade class.
        if( self::facade($class) !== false )
        {
            return;
        }
        
        # If a valid ClassMap file can not be found, this file is recreated.
        # Immediately before this build, the auto-installer performs a class 
        # lookup in the directories that are defined.
        if( ! is_file(self::$path) )
        {
            self::createClassMap();
        }

        # Getting information from the class map of the class being called according to ZN's autoloader.
        $classInfo = self::getClassFileInfo($class);

        # Retrieves the path information of the class to be loaded from the classmap file.
        $file = $classInfo['path'];
        
        # If the class file exists, it is included.
        if( is_file($file) )
        {
            require $file;

            # If the class file can not be loaded, the class map is rebuilt.
            if
            (
                ! class_exists($classInfo['namespace']) &&
                ! trait_exists($classInfo['namespace']) &&
                ! interface_exists($classInfo['namespace'])
            )
            {
                self::tryAgainCreateClassMap($class);
            }
        }
        # If the file of the invoked class does not contain a valid path, the class map is rebuilt.
        else
        {
            self::tryAgainCreateClassMap($class);
        }
    }

    /**
     * Autoload Facade
     * 
     * @param string $class
     * 
     * @return bool
     */
    public static function facade(String $class)
    {
        # The namespace of the invoked class is converted to path information.
        $path = str_replace('\\', '/', $class) . '.php';
        
        # If a facade class is called, this part goes into effect.
        if( strpos($class, 'ZN\\') !== 0 && is_file($file = (__DIR__ . '/Facades/' . $path)) )
        {   
            require $file; return;
        }
       
        return false;   
    }

    /**
     * Restarts the class mapping process.
     * 
     * @param void
     * 
     * @return void
     */
    public static function restart()
    {
        if( is_file(self::$path) )
        {
            unlink(self::$path);
        }

        return self::createClassMap();
    }

    /**
     * Starts the class mapping process.
     * 
     * @param void
     * 
     * @return void
     */
    public static function createClassMap()
    {
        # Clears file status cache.
        clearstatcache();

        # Getting predefined autoload settings.
        $configAutoloader = Config::get('Autoloader') ?: 
        # Default class map directory.
        # Applies to custom edition and individual package usage.
        [
            'directoryScanning' => true,
            'classMap'          => [REAL_BASE_DIR]
        ];

        # If the 'directoryScanning' value in the Settings/Autoloader.php 
        # setting file is set to false, it will not scan the directory
        # Setting this value to true is not recommended.
        if( $configAutoloader['directoryScanning'] === false )
        {
            return false;
        }

        # Directory information for class scanning is being retrieved.
        # Settings/Autoloader.php -> classMap key.
        $classMap = $configAutoloader['classMap'];
        
        # The classes are scanned in the specified directories.
        if( ! empty($classMap) ) foreach( $classMap as $directory )
        {
            $classMaps = self::searchClassMap($directory);
        }

        # The top output of the class map is being generated.
        self::createClassMapTopOutput($classMapPage);

        # Gets classes content.
        self::getClassesAndNamespacesOutput('classes', $classMaps, $classMapPage);

        # Gets namespaces content.
        self::getClassesAndNamespacesOutput('namespaces', $classMaps, $classMapPage);

        # It is checked whether the content to be newly added is empty.
        # 5.7.4.4[added|changed]
        self::addToClassMap($classMapPage);
    }

    /**
     * Protected create class map top output.
     */
    protected static function createClassMapTopOutput(&$classMapPage)
    {
        if( ! is_file(self::$path) )
        {
            $classMapPage  = '<?php'.EOL;
            $classMapPage .= '#----------------------------------------------------------------------'.EOL;
            $classMapPage .= '# This file automatically created and updated'.EOL;
            $classMapPage .= '#----------------------------------------------------------------------'.EOL;
        }
        else
        {
            $classMapPage = '';
        }
    }

    /**
     * Protected get classes & namespaces output
     */
    protected static function getClassesAndNamespacesOutput($type = '', $classMaps, &$classMapPage)
    {
         # Get the class and namespace array information from the Project/Any/ClassMap.php file
         $configClassMap = self::getClassMapContent();

        # Getting class paths to print on the class map.
        # For the concurrent correct class list, information is obtained from 
        # both the configuration file and the  $classes variable of this class.
        $classArray = array_diff_key
        (
            $classMaps[$type]      ?? [],
            $configClassMap[$type] ?? []
        );

        if( ! empty($classArray) )
        {
            self::${$type} = $classMaps[$type];

            foreach( $classArray as $k => $v )
            {
                $classMapPage .= '$classMap[\''.$type.'\'][\''.$k.'\'] = \''.$v.'\';'.EOL;
            }
        }
    }

    /**
     * Protected add to class map
     * 
     * 5.7.4.4[added]
     */
    protected static function addToClassMap($content)
    {
        if( ! is_file(self::$path) || (! empty($content) && ! strstr(file_get_contents(self::$path), $content)) )
        {
            file_put_contents(self::$path, $content, FILE_APPEND);
        }
    }

    /**
     * The invoked class holds the class, path, and namespace information.
     * 
     * @param string $class
     * 
     * @return array
     */
    public static function getClassFileInfo(String $class) : Array
    {
        $classCaseLower = strtolower($class);
        $classMap       = self::getClassMapContent();
        $classes        = array_merge($classMap['classes']    ?? [], (array) self::$classes);
        $namespaces     = array_merge($classMap['namespaces'] ?? [], (array) self::$namespaces);
        $path           = '';
        $namespace      = '';

        if( isset($classes[$classCaseLower]) )
        {
            $path      = $classes[$classCaseLower];
            $namespace = $class;
        }
        elseif( ! empty($namespaces) )
        {
            $namespaces = array_flip($namespaces);

            if( isset($namespaces[$classCaseLower]) )
            {
                $namespace = $namespaces[$classCaseLower];
                $path      = $classes[$namespace] ?? '';
            }
        }

        return
        [
            'path'      => $path,
            'class'     => $class,
            'namespace' => $namespace
        ];
    }

    /**
     * The path holds the class and namespace information of the specified class.
     * 
     * @param string $fileName
     * 
     * @return array
     */
    public static function tokenClassFileInfo(String $fileName) : Array
    {
        $classInfo = [];

        if( ! is_file($fileName) )
        {
            return $classInfo;
        }

        $tokens = token_get_all(file_get_contents($fileName));
        $i      = 0;
        $ns     = '';

        foreach( $tokens as $token )
        {
            if( $token[0] === T_NAMESPACE )
            {
                if( isset($tokens[$i + 2][1]) )
                {
                    if( ! isset($tokens[$i + 3][1]) )
                    {
                        $ns = $tokens[$i + 2][1];
                    }
                    else
                    {
                        $ii = $i;

                        while( isset($tokens[$ii + 2][1]) )
                        {
                            $ns .= $tokens[$ii + 2][1];

                            $ii++;
                        }
                    }
                }

                $classInfo['namespace'] = trim($ns);
            }

            if
            (
                $token[0] === T_CLASS     ||
                $token[0] === T_INTERFACE ||
                $token[0] === T_TRAIT
            )
            {
                $classInfo['class'] = $tokens[$i + 2][1] ?? NULL;

                break;
            }

            $i++;
        }

        return $classInfo;
    }

    /**
     * The location captures information from the specified file.
     * 
     * @param string $fileName
     * @param int    $type = T_FUNCTION
     * 
     * @return mixed
     */
    public static function tokenFileInfo(String $fileName, Int $type = T_FUNCTION)
    {
        if( ! is_file($fileName) )
        {
            return false;
        }

        $tokens = token_get_all(file_get_contents($fileName));
        $info   = [];

        $i = 0;

        foreach( $tokens as $token )
        {
            if( $token[0] === $type )
            {
                $info[] = $tokens[$i + 2][1] ?? NULL;
            }

            $i++;
        }

        return $info;
    }

    /**
     * If the use of alias is obvious, it will activate this operation.
     */
    protected static function aliases()
    {
        if( $autoloaderAliases = Config::get('Autoloader')['aliases'] ?? NULL ) foreach( $autoloaderAliases as $alias => $origin )
        {
            if( class_exists($origin) )
            {
                class_alias($origin, $alias);
            }
        }
    }

    /**
     * Search the invoked class in the classmap.
     * 
     * @param string $directory
     * 
     * @return mixed
     */
    protected static function searchClassMap($directory)
    {
        # Keeps a list of classes to be written to the class map.
        static $classes;

        # Directory path information to start scanning.
        $directory = Base::suffix($directory);

        # Gets the contents of the class map.
        $configClassMap = self::getClassMapContent();

        # Gets up the contents of the Settings/Autoloader.php 
        # file which holds the settings for this library.
        $configAutoloader = Config::get('Autoloader');

        # The list of files contained within the directory is retrieved.
        $files = glob($directory.'*');

        # The previously recorded class information on the list is eliminated.
        $files = array_diff
        (
            $files,
            $configClassMap['classes'] ?? []
        );

        # If the class is found in the scanned list, the class finder is started.
        if( ! empty($files) ) foreach( $files as $file )
        {
            # Continue scanning if the value is a file.
            if( is_file($file) )
            {
                # Class information about the file is retrieved.
                $classInfo = self::tokenClassFileInfo($file);

                # If the file contains valid class information, scanning continues.
                if( isset($classInfo['class']) )
                {
                    # Gets relative file path.
                    $file = self::getRelativeFilePath($file);
                 
                    # In the class map, array keys are kept in lower case.
                    $class = strtolower($classInfo['class']);
                    
                    # It is checked whether the scanned class a namespace. 
                    # According to this information class name is obtained.
                    if( isset($classInfo['namespace']) )
                    {
                        $className = strtolower($classInfo['namespace']).'\\'.$class;

                        # If the class contains a namespace, it is kept in the namespace array in the class map.
                        # This data is stored in the direct controller of a class that contains a namespace to 
                        # provide access to the $this object using only the class name.
                        $classes['namespaces'][self::cleanNailClassMapContent($className)] = self::cleanNailClassMapContent($class);
                    }
                    else
                    {
                        $className = $class;
                    }

                    # The name and path information of the scanned class is added to the class map.
                    $classes['classes'][self::cleanNailClassMapContent($className)] = self::cleanNailClassMapContent($file);

                    # If the scanned class has the prefix [Internal], 
                    # the static view of this class is created.
                    self::createStaticAccessClass($classInfo['class'], $file, $configAutoloader['directoryPermission'], $classes);
                }
            }
            # If the value is an index, resume the scan from that index.
            # Performs a nested directory scan until the file is found.
            elseif( is_dir($file) )
            {
                self::searchClassMap($file);
            }
        }

        return $classes;
    }

    /**
     * Protected create static access class
     */
    protected static function createStaticAccessClass($className, $file, $permission, &$classes)
    {
        if( stripos($className, INTERNAL_ACCESS) === 0 && ! preg_match('/(Interface|Trait)$/i', $className) )
        {
            # [Internal] prefix is cleared from class name.
            $originClassName = str_ireplace(INTERNAL_ACCESS, '', $className);
            
            # Static view is getting position information.
            $staticClassDirectory = pathinfo($file, PATHINFO_DIRNAME);
            
            # Static views are built into the Resources/Statics/ directory.
            $staticClassDirectory = self::$staticAccessDirectory . $staticClassDirectory;

            # The predefined authorization number for directories to which static views are written.
            $directoryPermission = $permission ?? 0755;
            
            # If the directory in which static views are to be created does not exist, 
            # it will be rebuilt.
            if( ! is_dir(self::$staticAccessDirectory) )
            {
                # Created Resources/Statics/ directory.
                mkdir(self::$staticAccessDirectory, $directoryPermission, true);

                # Access to this directory via URL is blocked.
                # It is assumed that the system is running on apache.
                file_put_contents(self::$staticAccessDirectory . '.htaccess', 'Deny from all');
            }

            # The static view creates a new directory with the same name into 
            # the Resources Statics/ directory according to the location of the original class.
            if( ! is_dir($staticClassDirectory) )
            {
                mkdir($staticClassDirectory, $directoryPermission, true);
            }

            # Static view file path information.
            $staticClassPath = Base::suffix($staticClassDirectory) . $className . '.php';
            
            # If constants are used in the scanned class, these constants are taken.
            $constants = self::findConstantsClassContent($file);

            # The static view of the scanned class is being created.
            $classContent = self::createClassFileContent($originClassName, $constants);

            # If a previously rendered static view of the scanned class has been created,
            # it is checked for changes in appearance before this static view is reconstructed.
            $fileContentLength = is_file($staticClassPath) ? strlen(file_get_contents($staticClassPath)) : 0;

            if( strlen($classContent) !== $fileContentLength )
            {
                # If the data do not match, recreate it.
                file_put_contents($staticClassPath, $classContent);
            }
            
            $classes['classes'][strtolower($originClassName)] = $staticClassPath;
        }
    }

    /**
     * It finds constants in the class.
     * 
     * @param string $v
     * 
     * @return string
     */
    protected static function findConstantsClassContent($v)
    {
        $getFileContent = file_get_contents($v);

        # If the classes in which the static view will be created contain constants, 
        # these constants are built into the static view.
        preg_match_all('/const\s+(\w+)\s+\=\s+(.*?);/i', $getFileContent, $match);

        $const = $match[1] ?? [];
        $value = $match[2] ?? [];

        $constants = '';

        if( ! empty($const) )
        {
            foreach( $const as $key => $c )
            {
                $constants .= HT."const ".$c.' = '.$value[$key].';'.EOL.EOL;
            }
        }

        return $constants;
    }

    /**
     * Creates internal class content.
     * 
     * @param string $newClassName
     * @param string $constants
     * 
     * @return string
     */
    protected static function createClassFileContent($newClassName, $constants)
    {
        # Static view of classes with prefix 'Internal'.
        # Static views are built into the Resources/Statics/ directory.
        $classContent  = '<?php'.EOL;
        $classContent .= '#-------------------------------------------------------------------------'.EOL;
        $classContent .= '# This file automatically created and updated'.EOL;
        $classContent .= '#-------------------------------------------------------------------------'.EOL.EOL;
        $classContent .= 'class '.$newClassName.' extends ZN\StaticAccess'.EOL;
        $classContent .= '{'.EOL;
        $classContent .= $constants;
        $classContent .= HT.'public static function getClassName()'.EOL;
        $classContent .= HT.'{'.EOL;
        $classContent .= HT.HT.'return __CLASS__;'.EOL;
        $classContent .= HT.'}'.EOL;
        $classContent .= '}'.EOL.EOL;
        $classContent .= '#-------------------------------------------------------------------------';

        return $classContent;
    }

    /**
     * Get config
     * 
     * @param void
     * 
     * @return mixed
     */
    private static function getClassMapContent()
    {
        # Some server configuration bugs may lead to erroneous writing to the class map. 
        # If a code error is detected in the possible class map, the class map is rebuilt. 
        # Thus, system operation is never interrupted.
        if( is_file(self::$path) )
        {
            global $classMap;
            
            # 5.4.61[added]
            try
            {
                require_once self::$path;
            }
            catch( \Throwable $e )
            {
                self::restart();
            }

            return $classMap;
        }

        return false;
    }

    /**
     * It attempts to construct the class map.
     * 
     * @param string $class
     * 
     * @return void
     */
    protected static function tryAgainCreateClassMap($class)
    {
        # The class map is being rebuilt.
        self::createClassMap();

        # Getting class information.
        $classInfo = self::getClassFileInfo($class);

        # The file location of the class is being obtained.
        $file = $classInfo['path'];

        # If the file location is correct, the class is included.
        if( is_file($file) )
        {
            require $file;
        }
    }

    /**
     * Protected get realative file path
     */
    protected static function getRelativeFilePath($file)
    {
        return str_replace(REAL_BASE_DIR, NULL, $file);
    }

    /**
     * Clean nail
     * 
     * @param string
     * 
     * @return string
     */
    protected static function cleanNailClassMapContent($string)
    {
        # If the class or namespace information contains quotes, these quotes are cleared.
        return str_replace(["'", '"'], NULL, $string);
    }

    /**
     * spl autoload register
     * 
     * @param string $type = 'run' - options[run|standart]
     * 
     * @return void
     */
    public static function register($type = 'run')
    {
        # Autoload register.
        spl_autoload_register('ZN\Autoloader::' . $type);

        # If the use of alias is obvious, it will activate this operation.
        self::aliases();
    }
}

# Alias Autoloader
class_alias('ZN\Autoloader', 'Autoloader');