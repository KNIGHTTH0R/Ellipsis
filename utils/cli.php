<?php

/**
 * CLI (Command-Line Interface)
 *
 * Improve the usability of a command-line PHP script by automating
 * common command-line tasks such as managing options, arguments,
 * inputs, colors, mirroring web environments, etc.
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 */

class CLI {

    /**
     * process running command line script
     *
     * @param string $usage
     * @return void
     */
    public static function process($usage){
        // ensure that STDIN is available
        if (!defined('STDIN')) define('STDIN', fopen('php://stdin', 'r'));

        // ensure that CLI is available
        if (!isset($_ENV['CLI'])) $_ENV['CLI'] = array();

        // parse and store usage instructions
        $data = self::parse($usage);

        // prepare for processing
        $args = $_SERVER['argv'];
        array_shift($args);
        $cmdline = implode(' ', $args);
        $data['opts'] = array();
        $data['args'] = array();

        // built in help flag
        if (preg_match('/--help/', $cmdline)){
            echo $data['help'];
            exit;
        }

        // process the environment
        $passed = true;
        foreach($data['options'] as $name => $option){
            if ($option['flag'] == null){
                // standalone
                if (preg_match('/--' . $option['name'] . '[\s$]/', $cmdline, $matches)){
                    $data['options'][$name]['value'] = true;
                    $cmdline = preg_replace('/' . $matches[0] . '/', '', $cmdline);
                }
            } else if ($option['label'] == null){
                // flag
                if (preg_match('/--' . $option['name'] . '[\s$]/', $cmdline, $matches)){
                    $data['options'][$name]['value'] = true;
                    $cmdline = preg_replace('/' . $matches[0] . '/', '', $cmdline);
                } else if (preg_match('/-' . $option['flag'] . '[\s$]/', $cmdline, $matches)){
                    $data['options'][$name]['value'] = true;
                    $cmdline = preg_replace('/' . $matches[0] . '/', '', $cmdline);
                }
            } else if ($option['label'] != null){
                // value
                if (preg_match('/--' . $option['name'] . '=([^\s$]+)/', $cmdline, $matches)){
                    $data['options'][$name]['value'] = $matches[1];
                    $cmdline = preg_replace('/' . $matches[0] . '/', '', $cmdline);
                } else if (preg_match('/-' . $option['flag'] . ' ([^\s$]+)/', $cmdline, $matches)){
                    $data['options'][$name]['value'] = $matches[1];
                    $cmdline = preg_replace('/' . $matches[0] . '/', '', $cmdline);
                }
            }
            if ($data['options'][$name]['required'] && !$data['options'][$name]['value']){
                $passed = false;
            } else {
                $data['opts'][strtolower($name)] = $data['options'][$name]['value'];
            }
        }
        if ($passed){
            $cmdline = trim($cmdline);
            $args = (strlen($cmdline) > 0) ? preg_split('/\s+/', $cmdline) : array();
            if (count($args) == count($data['arguments'])){
                if (count($args) > 0){
                    $count = 0;
                    foreach($data['arguments'] as $label => $argument){
                        $data['arguments'][$label]['value'] = $args[$count];
                        $data['args'][strtolower($label)] = $args[$count];
                        $count++;
                    }
                }
            } else {
                $passed = false;
            }
        }

        // built in debug flag
        if (preg_match('/--debug_cli/', $cmdline)){
            var_dump($data);
            exit;
        }

        if (!$passed){
            // show help
            echo $data['help'];
            exit;
        } else {
            // record results (for debugging)
            $_ENV['CLI'][self::id()] = $data;

            // simplify results as well
            $_ENV['args'] = $data['args'];
            $_ENV['opts'] = $data['opts'];
        }
    }

    /**
     * parse usage instructions
     *
     * Example usage block to pass to this method:
     *
     * Description:
     *    Give the description of this program here, it doesn't really matter what
     *    it says or how many lines it takes, it will be re-formatted as is appropriate.
     *    The usage line itself is also unnecessary because it is generated for you.
     *
     * Options:
     *    [-f, --flag]        This is an optional flag (-f or --flag)
     *    [-v, --value=VALUE] This is an optional value (-v VALUE or --value=VALUE)
     *    [--standalone]      This is an optional standalone flag
     *    -v2, --value2=VALUE This is a required value (-v VALUE or --value=VALUE)
     *
     * Arguments: 
     *    FILE2               This is a required argument
     *
     * @param string $usage
     * @return array
     */
    private static function parse($usage){
        // set the stage
        $parsed = array(
            'command'       => basename($_SERVER['argv'][0]),
            'description'   => null,
            'options'       => array(),
            'arguments'     => array(),
            'help'          => null
        );

        // process the usage statement according to each recognized block of info
        $lines = preg_split('/[\r\n]+/', $usage);
        $target = null;
        foreach($lines as $line){
            $line = trim($line);
            if (preg_match('/^([a-z]+)\s*:$/i', $line, $matches)){
                $target = strtolower($matches[1]);
                continue;
            }
            switch($target){
                case 'description':
                    $parsed['description'] .= "{$line} ";
                    break;
                case 'options':
                    $base = array(
                        'required'      => (preg_match('/^\[/', $line) ? false : true),
                        'flag'          => null,
                        'name'          => null,
                        'label'         => null,
                        'description'   => null,
                        'value'         => null,
                        'help'          => null
                    );
                    $option = null;
                    if (!$base['required'] && preg_match('/^[\[\s]*--([a-z0-9_]+)[\]\s]*(.+)$/i', $line, $matches)){
                        // standalone flag
                        $help = "    {$line}\n";
                        $option = array_merge($base, array('name' => $matches[1], 'description' => $matches[2], 'value' => false, 'help' => $help));
                    } else if (preg_match('/^[\[\s]*-([a-z0-9]+)\s*,\s*--([a-z0-9_=]+)[\]\s]*(.+)$/i', $line, $matches)){
                        // standard flag
                        if (preg_match('/=/', $matches[2])){
                            // value flag
                            list($name, $label) = preg_split('/=/', $matches[2]);
                            $help = "    {$line}\n";
                            $option = array_merge($base, array('flag' => $matches[1], 'name' => $name, 'label' => $label, 'description' => $matches[3], 'help' => $help));
                        } else if (!$base['required']){
                            // standard flag 
                            $help = "    {$line}\n";
                            $option = array_merge($base, array('flag' => $matches[1], 'name' => $matches[2], 'description' => $matches[3], 'value' => false, 'help' => $help));
                        }
                    }
                    if ($option != null) $parsed['options'][$option['name']] = $option;
                    break;
                case 'arguments':
                    $base = array(
                        'label'         => null,
                        'description'   => null,
                        'value'         => false,
                        'help'          => null
                    );
                    $argument = null;
                    if (preg_match('/^[\[\s]*([a-z0-9_]+)[\]\s]+(.+)$/i', $line, $matches)){
                        $help = "    {$line}\n";
                        $argument = array_merge($base, array('label' => $matches[1], 'description' => $matches[2], 'help' => $help));
                    }
                    if ($argument != null) $parsed['arguments'][$argument['label']] = $argument;
                    break;
            }
        }

        // format all of the required options for the usage statement
        $ropts = array();
        foreach($parsed['options'] as $option){
            if ($option['required'] && $option['label']){
                $ropts[] = "-{$option['flag']} {$option['label']}";
            }
        }

        // format all of the arguments for the usage statement
        $rargs = array();
        foreach($parsed['arguments'] as $argument){
            $rargs[] = "{$argument['label']}";
        }

        // create the help statement
        $parsed['help'] = "Usage:\n";
        $parsed['help'] .= "    {$parsed['command']} " . (count($parsed['options']) > 0 ? '[options...] ' : '') . implode(' ', $ropts) . ' ' . implode(' ', $rargs) . "\n\n";
        $parsed['help'] .= "Description:\n";
        $parsed['help'] .= "    " . implode("\n    ", explode("\n", wordwrap($parsed['description'], 74))) . "\n\n";
        if (count($parsed['options']) > 0){
            $parsed['help'] .= "Options:\n";
            foreach($parsed['options'] as $option){
                $parsed['help'] .= $option['help'];
            }
        }
        if (count($parsed['arguments']) > 0){
            $parsed['help'] .= "Arguments:\n";
            foreach($parsed['arguments'] as $argument){
                $parsed['help'] .= $argument['help'];
            }
        }
        $parsed['help'] .= "\n";

        // all done
        return $parsed;
    }

    /**
     * generate a unique id representing this command-line environment
     *
     * @param void
     * @return string
     */
    private static function id(){
        $unique_environment = array(
            $_ENV['_'],
            $_ENV['SHLVL'],
            $_ENV['TERM'],
            $_ENV['SHELL'],
            $_ENV['TMPDIR'],
            $_ENV['USER'],
            $_ENV['HOME'],
            $_ENV['COMMAND_MODE'],
            $_ENV['PATH'],
            $_SERVER['REQUEST_TIME'],
            $_SERVER['argv'][0]
        );
        return md5(serialize($unique_environment));
    }
}

