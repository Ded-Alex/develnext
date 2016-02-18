<?php
namespace ide\doc\commands;

use ide\editors\AbstractEditor;
use ide\Ide;
use ide\misc\AbstractCommand;
use ide\systems\FileSystem;
use ide\ui\Notifications;
use php\gui\layout\UXHBox;
use php\gui\text\UXFont;
use php\gui\UXButton;
use php\gui\UXSeparator;
use php\gui\UXTextField;

class DocCommand extends AbstractCommand
{
    public function isAlways()
    {
        return true;
    }

    public function getName()
    {
        return 'Помощь';
    }

    public function getCategory()
    {
        return 'help';
    }

    public function getIcon()
    {
        return 'icons/help16.png';
    }

    protected function makeSearchInputUi()
    {
        $input = new UXTextField();
        $input->promptText = 'поиск решений';
        $input->width = 170;
        $input->maxHeight = 999;
        $input->font = UXFont::of($input->font->family, 15);

        return $input;
    }

    public function makeUiForRightHead()
    {
        $button = $this->makeGlyphButton();
        $button->text = $this->getName();
        $button->maxHeight = 999;
        $button->style = '-fx-font-weight: bold;';
        $button->padding = [0, 15];

        $searchButton = new UXButton();
        $searchButton->classes->addAll(['icon-flat-search']);
        $searchButton->tooltipText = 'Поиск по документации';
        $searchButton->maxHeight = 999;
        $searchButton->width = 35;

        $ui = new UXHBox([$searchButton, $this->makeSearchInputUi(), $button]);
        $ui->spacing = 5;
        $ui->fillHeight = true;

        return $ui;
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        if (Ide::get()->isDevelopment()) {
            FileSystem::open('~doc');
        } else {
            Notifications::show('В разработке', 'Данная функция находится в разработке...', 'INFORMATION');
        }
    }
}