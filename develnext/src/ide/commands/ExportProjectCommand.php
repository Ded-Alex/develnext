<?php
namespace ide\commands;

use ide\forms\BuildSuccessForm;
use ide\Ide;
use ide\misc\AbstractCommand;
use php\gui\UXDialog;
use php\gui\UXFileChooser;
use php\gui\UXSeparator;
use php\io\File;
use php\lib\Str;

/**
 * Class ExportProjectCommand
 * @package ide\commands
 */
class ExportProjectCommand extends AbstractCommand
{
    public function getName()
    {
        return 'Сохранить как архив';
    }

    public function getIcon()
    {
        return 'icons/saveAs16.png';
    }

    public function getAccelerator()
    {
        return 'Ctrl + Shift + S';
    }

    public function makeUiForHead()
    {
        return [$this->makeGlyphButton(), new UXSeparator('VERTICAL')];
    }

    public function withBeforeSeparator()
    {
        return true;
    }

    public function isAlways()
    {
        return true;
    }

    public function onExecute()
    {
        $project = Ide::get()->getOpenedProject();

        if ($project) {
            $dialog = new UXFileChooser();
            $dialog->initialFileName = $project->getName() . ".zip";
            $dialog->extensionFilters = [['extensions' => ['*.zip'], 'description' => 'Zip Архив с проектом']];

            if ($file = $dialog->showSaveDialog()) {
                if (!Str::endsWith($file, '.zip')) {
                    $file .= '.zip';
                }

                $project->export($file);

                $dialog = new BuildSuccessForm();
                $dialog->setBuildPath($file);
                $dialog->setRunProgram(File::of($file));
                $dialog->setOpenDirectory(File::of($file)->getParent());

                $dialog->showAndWait();
            }
        } else {
            UXDialog::show('Для экспортирования необходимо открыть или создать проект');
        }
    }
}