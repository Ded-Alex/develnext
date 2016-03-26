<?php
namespace ide\utils;

use php\gui\UXDialog;
use php\io\File;
use php\io\FileStream;
use php\io\Stream;
use php\lang\System;
use php\lib\fs;
use php\lib\Str;

/**
 * Class FileUtils
 * @package ide\utils
 */
class FileUtils
{
    /**
     * @param $name
     * @return bool
     */
    public static function validate($name)
    {
        if (!fs::valid($name)) {
            UXDialog::show('Некорректное название, присутствуют системные символы, которые нельзя использовать в названии файлов.', 'ERROR');
            return false;
        }

        return true;
    }

    /**
     * @param string $path
     * @param callable $handle
     */
    public static function scan($path, callable $handle)
    {
        File::of($path)->find(function($dir, $name) use ($handle) {
            if ($name !== '.' && $name !== '..') {
                $filename = $dir . '/' . $name;

                $handle($filename);

                if (File::of($filename)->isDirectory()) {
                    FileUtils::scan($filename, $handle);
                }
            }
        });
    }

    public static function hash($file)
    {
        return File::of($file)->hash('SHA-256');
       // throw new \Exception("Not implemented");
    }

    /**
     * @param $name
     *
     * @return string
     */
    public static function hashName($name)
    {
        $name = Str::replace($name, '\\', '/');

        if (Str::contains(Str::lower(System::getProperty('os.name')), 'windows')) {
            $name = Str::lower($name);
        }

        return $name;
    }

    public static function adaptName($name)
    {
        $name = Str::replace($name, '\\', DIRECTORY_SEPARATOR);
        $name = Str::replace($name, '/', DIRECTORY_SEPARATOR);

        return $name;
    }

    public static function normalizeName($name)
    {
        $name = Str::replace($name, '\\', '/');

        $name = Str::replace($name, '/////', '/');
        $name = Str::replace($name, '////', '/');
        $name = Str::replace($name, '///', '/');
        $name = Str::replace($name, '//', '/');

        return $name;
    }

    /**
     * @param string $rootDir
     * @param string $path
     *
     * @return string
     */
    public static function relativePath($rootDir, $path)
    {
        $rootDir = self::normalizeName($rootDir);
        $path    = self::normalizeName($path);

        if (Str::contains(Str::lower(System::getProperty('os.name')), 'windows')) {
            if (Str::startsWith(Str::lower($path), Str::lower($rootDir))) {
                $path = Str::sub($path, Str::length($rootDir));
            }
        } else {
            $path = Str::replace($path, $rootDir, '');
        }

        $path = self::normalizeName($path);

        if (Str::startsWith($path, '/')) {
            $path = Str::sub($path, 1);
        }

        return $path;
    }

    public static function getExtension($name)
    {
        $pos = Str::lastPos($name, '.');

        if ($pos > -1) {
            return Str::sub($name, $pos + 1);
        }

        return null;
    }

    public static function stripExtension($name)
    {
        $pos = Str::lastPos($name, '.');

        if ($pos > -1) {
            $name = Str::sub($name, 0, $pos);
        }

        return $name;
    }

    public static function copyDirectory($directory, $newDirectory)
    {
        $directory = File::of($directory);
        $newDirectory = File::of($newDirectory);

        $newDirectory->mkdirs();

        self::scan($directory, function ($filename) use ($directory, $newDirectory) {
            $name = FileUtils::relativePath($directory, $filename);
            $newName = File::of("$newDirectory/$name");

            if (File::of($filename)->isDirectory()) {
                $newName->mkdirs();
            } else {
                FileUtils::copyFile($filename, $newName);
            }
        });
    }

    public static function copyFile($origin, $dest)
    {
        try {
            $in = Stream::of($origin);

            $parent = File::of($dest)->getParentFile();

            if ($parent && !$parent->isDirectory()) {
                $parent->mkdirs();
            }

            $out = new FileStream($dest, 'w+');
            return $out->write($in->readFully());
        } finally {
            if ($out) $out->close();
            if ($in) $in->close();
        }

        return -1;
    }

    public static function deleteDirectory($directory)
    {
        self::scan($directory, function ($filename) {
            $file = File::of($filename);

            if (!$file->isDirectory()) {
                $file->delete();
            }
        });

        self::scan($directory, function ($filename) {
            File::of($filename)->delete();
        });

        $directory = File::of($directory);
        $directory->delete();

        return ($directory->exists());
    }

    public static function put($filename, $content, $encoding = 'UTF-8')
    {
        fs::ensureParent($filename);

        Stream::putContents($filename, Str::encode($content, $encoding));
    }

    public static function get($filename, $encoding = 'UTF-8')
    {
        return Str::decode(Stream::getContents($filename), $encoding);
    }

    public static function equalNames($oneName, $twoName)
    {
        return self::hashName($oneName) == self::hashName($twoName);
    }
}