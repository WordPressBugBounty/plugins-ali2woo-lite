<?php

/**
 * Description of Loader
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;
use DI\Container;

#[\AllowDynamicProperties]
class Loader {

    const DEFAULT_INCLUDE_POSITION = 1000;
    const DEFAULT_INCLIDE_ACTION = 'global';

    static protected ?Loader $_instance = null;

    private function __construct() {
        
    }

    static public function getInstance() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    static public function classes($classpath, $default_load_action, Container $DI)
    {
        $this_class = Loader::getInstance();

        $result = $this_class->load_classpath($classpath, $default_load_action);

        foreach ($result['delay_include'] as $action => $files) {

            if ('global' === $action) {
                $include_array = array();
                foreach ($files as $file) {
                    $tmp = explode("###", $file);
                    $include_array[$tmp[0]] = $tmp[1];
                }
                asort($include_array);
                foreach ($include_array as $file => $p) {
                    include_once($file);
                }
            } else {
                $this_class->add_method($action . "_inclide", function () use (&$this_class, $action, $files) {
                    $include_array = array();
                    foreach ($files as $file) {
                        $tmp = explode("###", $file);
                        $include_array[$tmp[0]] = $tmp[1];
                    }
                    asort($include_array);
                    foreach ($include_array as $file => $p) {
                        include_once($file);
                    }
                });
                add_action($action, array($this_class, $action . "_inclide"), 10);
            }
        }

        foreach ($result['autoload'] as $action => $class_array) {
            if ('global' === $action) {
                foreach ($class_array as $className) {
                    if (
                        (defined('DOING_AJAX') && in_array($className, $result['skip_ajax'])) ||
                        (defined('DOING_CRON') && in_array($className, $result['skip_cron']))
                    ) {
                        continue;
                    }

                    $this_class->createClassInstance($className, $DI);
                }
            } else {
                $this_class->add_method($action, function () use (&$this_class, $action, $class_array, $result, $DI) {
                    foreach ($class_array as $className) {
                        if (
                            (defined('DOING_AJAX') && in_array($className, $result['skip_ajax'])) ||
                            (defined('DOING_CRON') && in_array($className, $result['skip_cron']))
                        ) {
                            continue;
                        }

                        $this_class->createClassInstance($className, $DI);
                    }
                });
                add_action($action, array($this_class, $action), 20);
            }
        }

     /*   foreach ($result['hook'] as $hook => $class_array_1) {
            foreach ($class_array_1 as $className1) {
                if (
                    (defined('DOING_AJAX') && in_array($className1, $result['skip_ajax'])) ||
                    (defined('DOING_CRON') && in_array($className1, $result['skip_cron']))
                ) {
                    continue;
                }
                $this_class->debug("Registering hook '{$hook}' with class '{$className1}'");
                $instance = $this_class->createClassInstance($className1, $DI);
            }
        }*/

        $this_class->add_method('finalize_hooks', function () use ($result, $DI, $this_class) {
            foreach ($result['hook'] as $hook => $classList) {
                foreach ($classList as $className) {
                    if (
                        (defined('DOING_AJAX') && in_array($className, $result['skip_ajax'])) ||
                        (defined('DOING_CRON') && in_array($className, $result['skip_cron']))
                    ) {
                        continue;
                    }
                    $this_class->debug("Registering hook '{$hook}' with class '{$className}'");
                    $instance = $this_class->createClassInstance($className, $DI);
                    if (method_exists($instance, '__invoke')) {
                        add_action($hook, $instance);
                    }
                }
            }
        });

        // And finally register a single WP hook to initialize this
        add_action('plugins_loaded', [$this_class, 'finalize_hooks']);
    }

    static public function addons($classpath) {
        if (substr($classpath, -1) !== "/") {
            $classpath.='/';
        }
        $dirs = glob($classpath . '*', GLOB_ONLYDIR);
        if ($dirs && is_array($dirs)) {
            foreach (glob($classpath . '*', GLOB_ONLYDIR) as $dir) {
                $file_list = scandir($dir . '/');
                foreach ($file_list as $f) {
                    if (is_file($dir . '/' . $f)) {
                        $file_info = pathinfo($f);
                        if ($file_info["extension"] == "php") {
                            include_once($dir . '/' . $f);
                        }
                    }
                }
            }
        }
    }

    private function debug(string $message): void
    {
        if (defined('A2WL_LOADER_DEBUG') && A2WL_LOADER_DEBUG === true) {
            error_log("[A2W Loader] " . $message);
        }
    }

    private function createClassInstance(string $className, Container $DI): object
    {
        $fqcn = 'AliNext_Lite\\' . $className;

        $this->debug("Instantiating: {$fqcn} via " . ($DI->has($fqcn) ? "DI container" : "constructor"));

        if ($DI->has($fqcn)) {
            //load class using DI id it has DI definition in config
            return $DI->get($fqcn);
        }

        return new $fqcn();
    }

    private function load_classpath($classpath, $default_load_action) {
        $result = array(
            'delay_include' => array(),
            'autoload' => array(),
            'skip_ajax' => array(),
            'skip_cron' => array(),
            'hook' => array()
        );
        
        if ($classpath) {
            $classpath .= substr($classpath, -1) === "/" ? "" : "/";

            $include_array = $subdir_array = array();

            foreach (glob($classpath . "*") as $f) {
                if (is_file($f)) {
                    $this->debug("Scanning file: {$f}");

                    $file_info = pathinfo($f);
                    if ($file_info["extension"] == "php") {
                        $file_data = get_file_data($f, array(
                            'position' => '@position',
                            'autoload' => '@autoload',
                            'include_action' => '@include_action',
                            'hook' => '@hook',
                            'ajax' => '@ajax',
                            'cron' => '@cron',
                        ));
                        if (isset($file_data['autoload']) && $file_data['autoload']) {
                            $action = (!is_null(filter_var($file_data['autoload'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE))) ? $default_load_action : $file_data['autoload'];
                            if (!isset($result['autoload'][$action])) {
                                $result['autoload'][$action] = array();
                            }

                            $this->debug("Autoloading: {$file_info['filename']} for action: {$action}");
                            $result['autoload'][$action][] = $file_info['filename'];

                            if(isset($file_data['ajax'])){
                                if(!filter_var($file_data['ajax'], FILTER_VALIDATE_BOOLEAN)){
                                    $result['skip_ajax'][] = $file_info['filename'];
                                }
                            }

                            if(isset($file_data['cron'])){
                                if(!filter_var($file_data['cron'], FILTER_VALIDATE_BOOLEAN)){
                                    $result['skip_cron'][] = $file_info['filename'];
                                }
                            }
                        }

                        if (!empty($file_data['hook'])) {
                            $action = $file_data['hook'];
                            if (!isset($result['hook'][$action])) {
                                $result['hook'][$action] = array();
                            }

                            $this->debug("Detected hook: {$file_data['hook']} for {$file_info['filename']}");
                            $result['hook'][$action][] = $file_info['filename'];

                            if(isset($file_data['ajax'])){
                                if(!filter_var($file_data['ajax'], FILTER_VALIDATE_BOOLEAN)){
                                    $result['skip_ajax'][] = $file_info['filename'];
                                }
                            }

                            if(isset($file_data['cron'])){
                                if(!filter_var($file_data['cron'], FILTER_VALIDATE_BOOLEAN)){
                                    $result['skip_cron'][] = $file_info['filename'];
                                }
                            }
                        }

                        if (isset($file_data['include_action']) && $file_data['include_action']) {
                            $include_action = (!is_null(filter_var($file_data['include_action'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE))) ? (filter_var($file_data['include_action'], FILTER_VALIDATE_BOOLEAN)?self::DEFAULT_INCLIDE_ACTION:"a2wl_fake_action") : $file_data['include_action'];
                            if (!isset($result['delay_include'][$include_action])) {
                                $result['delay_include'][$include_action] = array();
                            }

                            $this->debug("Including delayed file: {$f} for action: {$include_action}");
                            $result['delay_include'][$include_action][] = $f . "###" . (IntVal($file_data['position']) ? IntVal($file_data['position']) : self::DEFAULT_INCLUDE_POSITION);
                        } else {
                            $include_array[$f] = IntVal($file_data['position']) ? IntVal($file_data['position']) : self::DEFAULT_INCLUDE_POSITION;
                        }
                    }
                } else if (is_dir($f)) {
                    $subdir_array[] = $f;
                }
            }
            asort($include_array);
            foreach ($include_array as $file => $p) {
                include_once($file);
            }

            foreach ($subdir_array as $subdir) {
                $result = array_merge_recursive($result, $this->load_classpath($subdir, $default_load_action));
            }
        }

        return $result;
    }

    private function add_method($name, $method) {
        $this->{$name} = $method;
    }

    public function __call($name, $arguments) {
        return call_user_func($this->{$name}, $arguments);
    }

}
