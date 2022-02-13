<?php

declare(strict_types=1);

namespace JsonMachineTest;

class autoloaderTest extends \PHPUnit_Framework_TestCase
{
    public function testAutoloaderLoadsClass()
    {
        $dummyFile = $this->createAutoloadableClass();

        $autoloadersBackup = $this->unregisterCurrentAutoloaders();
        $autoloader = require __DIR__.'/../../autoloader.php';

        spl_autoload_register($autoloader);
        $autoloaded = class_exists('JsonMachine\\AutoloadStub');
        spl_autoload_unregister($autoloader);

        $this->registerPreviousAutoloaders($autoloadersBackup);

        $this->assertTrue($autoloaded);

        @unlink($dummyFile);
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
