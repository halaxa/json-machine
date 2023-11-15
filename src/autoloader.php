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
    /**
     * @return void
     */
    static public function autoloader(string $class)
    {
        $prefix = 'JsonMachine\\';
        $baseDir = __DIR__.DIRECTORY_SEPARATOR;

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $classWithoutPrefix = substr($class, $len);
        $file = $baseDir.str_replace('\\', '/', $classWithoutPrefix).'.php';

        if (file_exists($file)) {
            require $file;
        }
    }
}

// @codeCoverageIgnoreStart
return [Autoloading::class, 'autoloader'];
// @codeCoverageIgnoreEnd
