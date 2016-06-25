<?php

use facade\Json;
use php\format\JsonProcessor;
use php\framework\Logger;
use php\gui\framework\Application;
use php\gui\UXApplication;
use php\io\IOException;
use php\io\Stream;
use php\lang\ClassLoader;
use php\lang\Environment;
use php\lang\Module;
use php\lang\SourceMap;
use php\lib\fs;
use php\lib\str;
use php\time\Time;

define('DEVELNEXT_PROJECT_DEBUG', true);

try {
    Stream::putContents("application.pid", UXApplication::getPid());
} catch (IOException $e) {
    exit(1);
}

Logger::setLevel(Logger::LEVEL_DEBUG);

class DebugClassLoader extends ClassLoader
{
    /**
     * @var int
     */
    protected $allTime = 0;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var \php\lang\ThreadPool
     */
    protected $threadPool;

    /**
     * DebugClassLoader constructor.
     */
    public function __construct()
    {
        $this->cacheDir = "./.dn/cache/";

        if (!fs::exists($this->cacheDir) && !fs::makeDir($this->cacheDir)) {
            $this->cacheDir = null;
        }

        $this->threadPool = \php\lang\ThreadPool::create(1, 5, 30 * 1000);
    }

    public function __destruct()
    {
        $this->threadPool->shutdown();
    }

    public function loadClass($name)
    {
        $name = str::replace($name, '\\', '/');

        $filename = "res://$name.php";

        $t = Time::millis();
        $filenameEncoded = null;

        if ($this->cacheDir) {
            $filenameEncoded = $this->cacheDir . "bytecode/$name.phb";

            if (fs::isFile($filenameEncoded)) {
                $module = new Module($filenameEncoded, true);
                $module->call();

                $t = Time::millis() - $t;
                $this->allTime += $t;

                echo "[DEBUG] load cached '$filename', $t ms\n";
                return;
            }
        }

        try {
            $this->threadPool->execute(function () use ($filename) {
                $this->tryLoadSourceMap($filename);
            });

            $module = new Module($filename);
            $module->call();

            if ($filenameEncoded) {
                if (fs::ensureParent($filenameEncoded)) {
                    $module->dump($filenameEncoded);
                }
            }

            //require $filename;

            $t = Time::millis() - $t;
            $this->allTime += $t;

            echo "[DEBUG] load '$filename', $t ms\n";
        } catch (IOException $e) {
            ;
        }

        // echo "[DEBUG] require '$filename', $t ms ($this->allTime ms)\n";
    }

    public function tryLoadSourceMap($filename)
    {
        $sourceMapFile = $filename . ".sourcemap";

        try {
            $json = new JsonProcessor();

            $map = (array) $json->parse(Stream::of($sourceMapFile));

            if ($map) {
                $sourceMap = new SourceMap($filename);

                foreach ($map as $cLine => $sLine) {
                    $sourceMap->addLine($sLine, $cLine);
                }

                Environment::current()->registerSourceMap($sourceMap);
            }
        } catch (IOException $e) {
            ;
        }
    }
}

$debugClassLoader = new DebugClassLoader();
$debugClassLoader->register(true);