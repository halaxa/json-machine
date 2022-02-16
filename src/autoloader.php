<?php

declare(strict_types=1);

namespace JsonMachine;

/**
 * A simple PSR-4 spec auto loader to allow json-machine to function the same as if it were loaded via Composer.
 *
 * To use this just include this file in your script and the JsonMachine namespace will be made available
 *
 * Usage: spl_autoload_register(require '/path/to/json-machine/src/autoloader.php');
 *
 * See: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
 *
 * @param string $class the fully-qualified class name
 *
 * @return void
 */
class Autoloading
{
    static public function autoloader($class)
    {
        // project-specific namespace prefix
        $prefix = 'JsonMachine\\';

        // base directory for the namespace prefix
        $base_dir = __DIR__ . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR;

        // does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            // no, move to the next registered autoloader
            return;
        }

        // get the relative class name
        $relative_class = substr($class, $len);

        // replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        // if the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    }
}

return [Autoloading::class, 'autoloader'];
