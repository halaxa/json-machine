<?php

declare(strict_types=1);

namespace JsonMachineTest;

/**
 * @covers \JsonMachine\Autoloading
 */
class AutoloadingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testAutoloaderLoadsClass()
    {
        $dummyFile = $this->createAutoloadableClass();
        register_shutdown_function(function () use ($dummyFile) {
            @unlink($dummyFile);
        });

        $autoloadersBackup = $this->unregisterCurrentAutoloaders();
        $autoloader = require __DIR__.'/../../src/autoloader.php';

        spl_autoload_register($autoloader);
        $autoloaded = class_exists('JsonMachine\\AutoloadStub');
        spl_autoload_unregister($autoloader);

        $this->registerPreviousAutoloaders($autoloadersBackup);

        $this->assertTrue($autoloaded);
    }

    /**
     * @runInSeparateProcess
     */
    public function testIgnoresInvalidBaseNamespace()
    {
        $dummyFile = $this->createAutoloadableClass();
        register_shutdown_function(function () use ($dummyFile) {
            @unlink($dummyFile);
        });

        $autoloadersBackup = $this->unregisterCurrentAutoloaders();
        $autoloader = require __DIR__.'/../../src/autoloader.php';

        spl_autoload_register($autoloader);
        $autoloaded = class_exists('XXXsonMachine\\AutoloadStub');
        spl_autoload_unregister($autoloader);

        $this->registerPreviousAutoloaders($autoloadersBackup);

        $this->assertFalse($autoloaded);
    }

    private function createAutoloadableClass(): string
    {
        $dummyFile = __DIR__.'/../../src/AutoloadStub.php';
        file_put_contents($dummyFile, '<?php namespace JsonMachine; class AutoloadStub {}');

        return $dummyFile;
    }

    private function unregisterCurrentAutoloaders(): array
    {
        $autoloadersBackup = [];
        foreach (spl_autoload_functions() as $autoloader) {
            $autoloadersBackup[] = $autoloader;
            spl_autoload_unregister($autoloader);
        }

        return $autoloadersBackup;
    }

    private function registerPreviousAutoloaders(array $autoloadersBackup)
    {
        foreach ($autoloadersBackup as $restoreAutoloader) {
            spl_autoload_register($restoreAutoloader);
        }
    }
}
