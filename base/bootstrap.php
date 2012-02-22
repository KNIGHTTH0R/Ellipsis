<?php

/**
 * Ellipsis Bootstrap
 *
 * "Kick the tires and light the fires, big daddy."
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 */

// global settings
ini_set('error_reporting', E_ALL | E_STRICT);
ini_set('display_errors', 'Off');
ini_set('log_errors', 'Off');
// @todo: add errors to the website log file

// create a place to store boot errors pre-Ellipsis
$_ENV['BOOT_ERRORS'] = array();

/**
 * autoload application libraries
 *
 * @param string $app_name
 * @return void
 */
function ellipsis_autoload_libraries($app_name){
    if (isset($_ENV['APPS'][$app_name])){
        foreach($_ENV['APPS'][$app_name]['APP_LIB_FILES'] as $library){
            include $library;
        }
    }
}

/**
 * autoload application modules
 *
 * @param string $class_name
 * @return void
 */
function ellipsis_autoload_modules($class_name){
    if (preg_match('/^[a-z0-9]+$/i', $class_name)){
        // determine which apps are authorized (and in which order) to load this class
        $all = array_reverse(array_keys($_ENV['APPS']));
        $authorized = array();
        if ($_ENV['CURRENT'] != null){
            $found = false;
            foreach($all as $app_name){
                if ($found) $authorized[] = $app_name;
                if ($app_name == $_ENV['CURRENT']) $found = true;
            }
        } else {
            $authorized = $all;
        }

        // locate and load this class
        foreach($authorized as $app_name){
            foreach($_ENV['APPS'][$app_name]['APP_MODULES_FILES'] as $module){
                if (preg_replace('/^.*([^\/]+)\.php$/U', '$1', $module) == strtolower($class_name)){
                    require_once($module);
                    return;
                }
            }
        }
    }
    //throw new Exception("Unable to load {$class_name}");
}

// begin processing applications
if (count($_ENV['RUN']) >= 1){
    // register module autoloader
    spl_autoload_register('ellipsis_autoload_modules');

    // uncover any mystery
    if (!preg_match('/\/htdocs$/', $_SERVER['DOCUMENT_ROOT']) && is_link($_SERVER['DOCUMENT_ROOT'])){
        $real = readlink($_SERVER['DOCUMENT_ROOT']);
        if (preg_match('/\/htdocs$/', $real)){
            $_SERVER['DOCUMENT_ROOT'] = $real;
        }
    }

    // validate and load the website configuration
    if (preg_match('/\/htdocs$/', $_SERVER['DOCUMENT_ROOT'])){
        $website_root       = preg_replace('/\/htdocs$/', '', $_SERVER['DOCUMENT_ROOT']);
        $website_name       = preg_replace('/^.*\/([^\/]+)$/', '$1', $website_root);
        $website_config     = "{$website_root}/config.php";
        $website_routes     = "{$website_root}/routes.php";
        $website_cache      = "{$website_root}/cache";
        $website_logs       = "{$website_root}/logs";

        if (is_dir($website_root) && is_file($website_config) && is_file($website_routes) && is_dir($website_cache) && is_dir($website_logs)){
            $_ENV['WEBSITE_NAME']           = $website_name;
            $_ENV['WEBSITE_ROOT']           = $website_root;
            $_ENV['WEBSITE_CONFIG_FILE']    = $website_config;
            $_ENV['WEBSITE_ROUTES_FILE']    = $website_routes;
            $_ENV['WEBSITE_CACHE_ROOT']     = $website_cache;
            $_ENV['WEBSITE_LOG_ROOT']       = $website_logs;
            $_ENV['WEBSITE_ROUTES']         = array();

            // validate and load each application configuration
            $apps = array();
            $apps_dir = dirname(__FILE__) . '/apps';
            if (is_dir($apps_dir)){
                foreach($_ENV['RUN'] as $app_dir){
                    $app_name       = preg_replace('/-.*$/', '', $app_dir);
                    $app_root       = "{$apps_dir}/{$app_dir}";
                    $app_file       = "{$app_root}/{$app_name}.php";
                    $app_config     = "{$app_root}/config.php";
                    $app_routes     = "{$app_root}/routes.php";
                    $app_lib        = "{$app_root}/lib";
                    $app_modules    = "{$app_root}/modules";
                    $app_src        = "{$app_root}/src";
                    $app_tests      = "{$app_root}/tests";

                    if (is_dir($app_root) && is_file($app_file) && is_file($app_config) && is_file($app_routes) && is_dir($app_lib) && is_dir($app_modules) && is_dir($app_src) && is_dir($app_tests)){
                        $apps[$app_name] = array(
                            'APP_NAME'          => $app_name,       // the app name (after hyphen truncation)
                            'APP_ROOT'          => $app_root,       // the app root directory under which {app_name}.php is executing
                            'APP_FILE'          => $app_file,       // the app file path (i.e. {app_name}.php)
                            'APP_CONFIG_FILE'   => $app_config,     // the app config file path
                            'APP_ROUTES_FILE'   => $app_routes,     // the app routes file path
                            'APP_LIB_ROOT'      => $app_lib,        // the app lib root directory (includable libraries)
                            'APP_MODULES_ROOT'  => $app_modules,    // the app modules root directory (loadable modules)
                            'APP_SRC_ROOT'      => $app_src,        // the app source root directory (loadable source files)
                            'APP_TEST_ROOT'     => $app_tests,      // the app unit test root directory (unit test files)
                            'APP_ROUTES'        => array()          // the app routes (from $app_name/routes.php)
                        );
                    } else {
                        $_ENV['BOOT_ERRORS'][] = "The application `{$app_name}` is either invalid, missing or configured incorrectly";
                    }
                }

                // process each app
                if (count($_ENV['BOOT_ERRORS']) == 0 && count($apps) == count($_ENV['RUN']) && isset($apps['ellipsis'])){
                    // ensure that Ellipsis is the first app
                    $_ENV['APPS'] = array('ellipsis' => $apps['ellipsis']);
                    foreach($apps as $app_name => $app){
                        if ($app_name != 'ellipsis'){
                            $_ENV['APPS'][$app_name] = $app;
                        }
                    }

                    // reset PATH_INFO (this was deprecated in PHP but I still use it)
                    $_SERVER['PATH_INFO'] = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);

                    // process app includables, loadables, and configurations
                    foreach($_ENV['APPS'] as $app_name => $app){
                        // identify includable app libraries
                        $_ENV['APPS'][$app_name]['APP_LIB_FILES'] = glob("{$app['APP_LIB_ROOT']}/*.php");

                        // identify loadable app modules
                        $_ENV['APPS'][$app_name]['APP_MODULES_FILES'] = glob("{$app['APP_MODULES_ROOT']}/*.php");

                        // set app configs
                        include $app['APP_CONFIG_FILE'];
                    }

                    // set website configs
                    include $_ENV['WEBSITE_CONFIG_FILE'];

                    // load app classes and routes
                    foreach($_ENV['APPS'] as $app_name => $app){
                        // set current app
                        $_ENV['CURRENT'] = $app_name;

                        // load app libraries
                        ellipsis_autoload_libraries($app_name);

                        // load app class
                        include $app['APP_FILE'];

                        // add app routes
                        include $app['APP_ROUTES_FILE'];
                    }

                    // reset app for website configurations
                    $_ENV['CURRENT'] = null;

                    // add website routes
                    include $_ENV['WEBSITE_ROUTES_FILE'];

                    // stack all routes in one reverse order list
                    $all = array();
                    foreach(array_keys($_ENV['APPS']) as $app_name){
                        $all = array_merge($all, $_ENV['APPS'][$app_name]['APP_ROUTES']);
                    }
                    $all = array_merge($all, $_ENV['WEBSITE_ROUTES']);

                    // apply route override logic
                    $overrides = array();
                    $processed = array();
                    foreach($all as $route){
                        if ($route['override'] && !isset($overrides[$route['hash']])){
                            $overrides[$route['hash']] = $route;
                        }
                    }
                    $count = 0;
                    foreach($all as $route){
                        if (isset($overrides[$route['hash']]) && !isset($processed[$route['hash']])){
                            $processed[$route['hash']] = $overrides[$route['hash']];
                        } else if (!isset($overrides[$route['hash']])){
                            $processed[$count] = $route;
                            $count++;
                        }
                    }
                    $processed = array_values($processed);

                    // set route list
                    $_ENV['ROUTES'] = $processed;

                    // execute Ellipsis
                    Ellipsis::execute();
                }
            } else {
                $_ENV['BOOT_ERRORS'][] = "The apps directory could not be found";
            }
        } else {
            $_ENV['BOOT_ERRORS'][] = "The website profile is either invalid, missing or configured incorrectly";
        }
    } else {
        $_ENV['BOOT_ERRORS'][] = "Could not find the htdocs directory in the website profile";
    }
} else {
    $_ENV['BOOT_ERRORS'][] = "No applications were specified";
}

if (count($_ENV['BOOT_ERRORS']) > 0){
    echo "<h1>Boot Errors</h1><ul><li>" . implode("</li><li>", $_ENV['BOOT_ERRORS']) . "</li></ul>";
}

?>
