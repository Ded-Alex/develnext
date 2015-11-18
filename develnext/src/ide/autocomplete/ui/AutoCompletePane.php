<?php
namespace ide\autocomplete\ui;

use ide\autocomplete\AutoComplete;
use ide\autocomplete\AutoCompleteItem;
use ide\autocomplete\AutoCompleteType;
use ide\autocomplete\MethodAutoCompleteItem;
use ide\autocomplete\PropertyAutoCompleteItem;
use ide\autocomplete\VariableAutoCompleteItem;
use ide\Logger;
use php\gui\designer\UXSyntaxTextArea;
use php\gui\event\UXKeyEvent;
use php\gui\layout\UXHBox;
use php\gui\layout\UXVBox;
use php\gui\paint\UXColor;
use php\gui\text\UXFont;
use php\gui\UXApplication;
use php\gui\UXLabel;
use php\gui\UXListCell;
use php\gui\UXListView;
use php\gui\UXPopupWindow;
use php\lib\Char;
use php\lib\Items;
use php\lib\Str;
use php\util\Flow;

class AutoCompletePane
{
    /**
     * @var UXPopupWindow
     */
    protected $ui;

    /**
     * @var UXSyntaxTextArea
     */
    protected $area;

    /**
     * @var AutoComplete
     */
    protected $complete;

    /**
     * @var UXListView
     */
    protected $list;

    /**
     * @var bool
     */
    protected $visible;

    /**
     * @var AutoCompleteType[]
     */
    protected $types = [];

    /**
     * @var bool
     */
    protected $shown = false;

    /**
     * @var null
     */
    protected $lastString = null;

    protected $lock = false;

    public function __construct(UXSyntaxTextArea $area, AutoComplete $complete)
    {
        $this->area = $area;
        $this->complete = $complete;
        $this->makeUi();
        $this->init();
    }

    protected function init()
    {
        $this->area->on('keyDown', function (UXKeyEvent $e) {
            $this->area->data('oldCaretPosition', $this->area->caretPosition);

            switch ($e->codeName) {
                case 'Up':
                    if ($this->doUp()) {
                        $e->consume();
                    }
                    break;

                case 'Down':
                    if ($this->doDown()) {
                        $e->consume();
                    }
                    break;

                case 'Enter':
                    if ($this->doPick()) {
                        $e->consume();
                    }
                    break;

                case 'Esc':
                    $this->ui->hide();
                    $e->consume();
                    break;

                default:
                    $this->complete->update($this->area->text);
                    break;
            }

        }, __CLASS__);

        $this->area->on('keyUp', function (UXKeyEvent $e) {
            switch ($e->codeName) {
                case 'Up':
                case 'Down':
                    return;
            }

            if ($this->lock) {
                $this->lock = false;
                return;
            }

            list($x, $y) = $this->area->getCaretScreenPosition();

            if ($string = $this->getString()) {
                $items = null;

                if ($string != $this->lastString) {
                    $types = $this->complete->identifyType($string);

                    if (Items::keys($this->types) != $types) {
                        $this->types = [];

                        foreach ($types as $type) {
                            if (!$this->hasType($type)) {
                                $this->addType($type);
                            }
                        }
                    }

                    $items = $this->makeItems($this->getString(true));
                }

                $this->lastString = $string;

                UXApplication::runLater(function () use ($x, $y, $string, $items) {
                    if ($items !== null) {
                        $this->list->items->clear();
                        $this->list->items->addAll($items);
                        $this->list->selectedIndex = 0;

                        Logger::debug("Autocomplete list updated.");
                    }

                    if ($this->list->items->count) {
                        $this->show($x + 45, $y + 20);
                    } else {
                        Logger::debug("No auto complete items for: $string");
                        $this->hide();
                    }
                });
            } else {
                $this->hide();
            }
        }, __CLASS__);
    }

    public function show($x, $y) {
        UXApplication::runLater(function () use ($x, $y) {
            if (!$this->shown) {
                $this->list->selectedIndex = 0;
            }

            $size = min([$this->list->items->count, 10]);

            $this->ui->layout->maxHeight = $size * ($this->list->fixedCellSize) + 6 + 2 + $this->list->fixedCellSize;

            $this->ui->show($this->area->form, $x, $y);
            $this->shown = true;
        });
    }

    public function hide() {
        UXApplication::runLater(function () {
            $this->shown = false;
            $this->ui->hide();
        });
    }

    protected function restoreCaret()
    {
        $this->area->caretPosition = $this->area->data('oldCaretPosition');
    }

    protected function doUp()
    {
        if ($this->shown) {
            UXApplication::runLater(function () {
                $this->list->selectedIndex -= 1;

                if ($this->list->selectedIndex == -1) {
                    $this->list->selectedIndex = $this->list->items->count - 1;
                }
            });
            return true;
        }
    }

    protected function doDown()
    {
        if ($this->shown) {
            UXApplication::runLater(function () {
                $this->list->selectedIndex += 1;

                if ($this->list->selectedIndex == -1) {
                    $this->list->selectedIndex = 0;
                }
            });
            return true;
        }
    }

    protected function doPick()
    {
        Logger::debug("do pick");

        if ($this->shown) {
            UXApplication::runLater(function () {
                $this->hide();

                /** @var AutoCompleteItem $selected */
                $selected = Items::first($this->list->selectedItems);

                $prefix = $this->getString(true);

                $insert = $selected->getInsert();

                if (Str::startsWith($insert, $prefix)) {
                    $insert = Str::sub($insert, Str::length($prefix));
                }

                Logger::debug("Insert to caret: " . $insert);
                $this->area->insertToCaret($insert);
            });
            $this->lock = true;
            return true;
        }
    }

    public function hasType($name)
    {
        return isset($this->types[$name]);
    }

    public function addType($name)
    {
        $type = $this->complete->fetchType($name);

        if ($type) {
            $this->types[$name] = $type;
        }
    }

    public function getString($onlyName = false)
    {
        $text = $this->area->text;

        $i = $this->area->caretPosition;

        $string = '';

        while ($i-- >= 0) {
            $ch = $text[$i];

            if (Char::isPrintable($ch)
                && (Char::isLetterOrDigit($ch) || (!$onlyName && Str::contains('!@#$%^&*()_+=-,./\\:;|><{}[]"\'', $ch)))) {
                $string .= $ch;
            } else {
                break;
            }
        }

        return Str::reverse($string);
    }

    public function add($string)
    {
        $this->list->items->add($string);
    }

    private function makeUi()
    {
        $ui = new UXVBox();
        $ui->height = 150;
        $ui->maxWidth = 650;
        $ui->focusTraversable = false;
        $ui->padding = 3;

        $list = new UXListView();
        $list->maxHeight = 9999;
        $list->fixedCellSize = 20;
        $list->style = '-fx-font-family: "Courier new"';
        $list->width = 450;

        $ui->add($list);
        $ui->focusTraversable = false;
        UXVBox::setVgrow($list, 'ALWAYS');

        $list->setCellFactory(function (UXListCell $cell, AutoCompleteItem $item) {
            $cell->graphic = null;
            $cell->text = null;
            $this->makeItemUi($cell, $item);
        });

        $this->list = $list;

        $ui->observer('visible')->addListener(function ($old, $new) {
            $this->visible = $new;
        });

        $win = new UXPopupWindow();
        $win->layout = $ui;
        $win->width = 450;

        $list->on('click', function () {
            $this->doPick();
        });

        $list->on('keyDown', function (UXKeyEvent $e) {
            switch ($e->codeName) {
                case 'Enter':
                    if ($this->doPick()) {
                        $e->consume();
                    }
                    break;

                case 'Esc':
                    $this->hide();
                    $e->consume();
                    $this->lock = true;
                    break;
            }
        });

        return $this->ui = $win;
    }

    private function makeItems($prefix = '')
    {
        $flow = Flow::ofEmpty();

        $region = $this->complete->findRegion($this->area->caretLine, $this->area->caretOffset);

        foreach ($this->types as $type) {
            $flow = $flow
                ->append($type->getStatements($this->complete, $region))
                ->append($type->getConstants($this->complete, $region))
                ->append($type->getMethods($this->complete, $region))
                ->append($type->getProperties($this->complete, $region))
                ->append($type->getVariables($this->complete, $region));
        }

        if ($prefix) {
            $flow = $flow->find(function (AutoCompleteItem $one) use ($prefix) {
                return Str::startsWith($one->getName(), $prefix);
            });
        }

        $items = $flow->sort(function (AutoCompleteItem $one, AutoCompleteItem $two) {
            if ($one->getName() == $two->getName()) {
                return 0;
            }

            return Str::compare($one->getName(), $two->getName());
        });

        return ($items);
    }

    protected function makeItemUi(UXListCell $cell, AutoCompleteItem $item)
    {
        $label = new UXLabel($item->getName());
        $label->textColor = UXColor::of('black');
        $label->style = '-fx-font-weight: bold;';

        if (!$item->getDescription()) {
            $cell->graphic = $label;
        } else {
            $hintLabel = new UXLabel($item->getDescription() ? ": {$item->getDescription()}" : "");
            $hintLabel->textColor = UXColor::of('gray');

            $cell->graphic = new UXHBox([$label, $hintLabel]);
        }

        if ($item instanceof VariableAutoCompleteItem) {
            $label->text = "\${$label->text}";
            $label->textColor = UXColor::of('blue');
        }

        if ($item instanceof MethodAutoCompleteItem) {
            $label->text .= '()';
            $label->textColor = UXColor::of('darkblue');
        }

        if ($item instanceof PropertyAutoCompleteItem) {
            $label->text = "->{$label->text}";
            $label->textColor = UXColor::of('green');
        }
    }
}