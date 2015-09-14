<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use php\io\File;
use php\lib\Str;

class IfDirExistsActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'file' => 'string',
            'not' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'file' => 'Путь к папке',
            'not' => 'Отрицание (наоборт, если не существует)'
        ];
    }

    function isAppendSingleLevel()
    {
        return true;
    }

    function getGroup()
    {
        return self::GROUP_CONDITIONS;
    }

    function getTagName()
    {
        return 'ifDirExists';
    }

    function getTitle(Action $action = null)
    {
        if (!$action || !$action->not) {
            return 'Если есть папка ...';
        } else {
            return 'Если нет папки ...';
        }
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "Если существует папка";
        }

        if ($action->not) {
            return Str::format("Если НЕ существует папка %s", $action->get('file'));
        } else {
            return Str::format("Если существует папка %s", $action->get('file'));
        }
    }

    function getIcon(Action $action = null)
    {
        return 'icons/ifDir16.png';
    }

    function imports()
    {
        return [
            File::class
        ];
    }

    /**
     * @param Action $action
     * @return string
     */
    function convertToCode(Action $action)
    {
        $file = $action->get('file');

        if ($action->not) {
            return "if (!File::of({$file})->isDirectory())";
        } else {
            return "if (File::of({$file})->isDirectory())";
        }
    }
}