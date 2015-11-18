<?php
namespace ide\library;

use Files;
use ide\Ide;
use ide\Logger;
use ide\utils\FileUtils;
use php\io\File;
use php\lang\IllegalArgumentException;
use php\lang\System;
use php\lib\Str;

/**
 * Class IdeLibrary
 * @package ide\library
 */
class IdeLibrary
{
    private $categories = [
        'projects' => [
            'title' => 'Проекты',
            'type' => 'ide\library\IdeLibraryProjectResource',
        ],
        'scripts' => [
            'title' => 'Скрипты',
            'type' => 'ide\library\IdeLibrarySnippetResource',
        ],
        'images' => [
            'title' => 'Изображения',
            'type' => 'ide\library\IdeLibraryImageResource',
        ]
    ];

    /**
     * @var Ide
     */
    protected $ide;

    /**
     * @var File
     */
    protected $defaultDirectory;

    /**
     * @var File
     */
    protected $directories = [];

    /**
     * @var array
     */
    protected $resources = [];

    /**
     * IdeLibrary constructor.
     * @param Ide $ide
     */
    public function __construct(Ide $ide)
    {
        $this->ide = $ide;

        $home = System::getProperty('user.home');

        $this->defaultDirectory = File::of("$home/DevelNextLibrary");
        $this->directories[] = $ide->getOwnFile("library");

        if ($ide->isDevelopment()) {
            $this->directories[] = $ide->getOwnFile("misc/library");
        }

        if (!$this->defaultDirectory->isDirectory()) {
            if (!$this->defaultDirectory->mkdirs()) {
                Logger::error('Cannot create default library directory');
            }
        }

        $ide->on('start', function () {
            $this->update();
        });
    }

    public function update()
    {
        Logger::info("Update ide library resources ...");

        $directories = $this->directories;
        $directories[] = $this->defaultDirectory;

        $this->resources = [];

        foreach ($this->categories as $code => $type) {
            foreach ($directories as $directory) {
                Logger::info("Scan library resource directory - $directory/$code, type = $type[type]");

                FileUtils::scan("$directory/$code", function ($filename) use ($code, $type) {
                    if (Str::endsWith($filename, '.resource')) {
                        $path = FileUtils::stripExtension($filename);

                        Logger::info("Add library resource $filename, type = $type[type]");
                        $this->resources[$code][] = new $type['type']($path);
                    }
                });
            }
        }
    }

    /**
     * @param $category
     * @return IdeLibraryResource[]
     */
    public function getResources($category)
    {
        return (array) $this->resources[$category];
    }

    /**
     * @param string $category
     * @param string $name
     * @param bool $rewrite
     * @throws IllegalArgumentException
     * @return IdeLibraryResource
     */
    public function makeResource($category, $name, $rewrite = false)
    {
        $info = $this->categories[$category];

        if (!$info) {
            throw new IllegalArgumentException('Invalid category');
        }

        $path = "$this->defaultDirectory/$category/$name";

        if (!$rewrite && Files::exists("$path.resource")) {
            return null;
        } else {
            Files::delete("$path.resource");
            Files::delete("$path");
        }

        return new $info['type']($path);
    }

    public function delete(IdeLibraryResource $resource)
    {
        $resource->delete();
        $this->update();
    }
}