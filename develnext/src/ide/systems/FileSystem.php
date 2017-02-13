<?php
namespace ide\systems;

use ide\editors\AbstractEditor;
use ide\editors\form\IdeTabPane;
use ide\editors\menu\ContextMenu;
use ide\forms\MainForm;
use ide\Ide;
use ide\Logger;
use ide\utils\FileUtils;
use ide\utils\Json;
use php\gui\event\UXEvent;
use php\gui\event\UXMouseEvent;
use php\gui\layout\UXAnchorPane;
use php\gui\layout\UXScrollPane;
use php\gui\UXApplication;
use php\gui\UXButton;
use php\gui\UXContextMenu;
use php\gui\UXDialog;
use php\gui\UXSplitPane;
use php\gui\UXTab;
use php\gui\UXMenu;
use php\gui\UXTabPane;
use php\io\File;
use php\lib\arr;
use php\lib\fs;
use php\lib\Items;

class FileSystem
{
    /**
     * @var AbstractEditor[]
     */
    static protected $openedEditors = [];

    /**
     * @var AbstractEditor[]
     */
    static protected $cachedEditors = [];

    /**
     * @var UXTab
     */
    static protected $addTab;

    /**
     * @var callable
     */
    static protected $addTabClick;

    /**
     * @var UXTab[]
     */
    static protected $openedTabs = [];

    /**
     * @var array
     */
    static protected $openedFiles = [];

    /**
     * @var AbstractEditor
     */
    static protected $previousEditor = null;

    /**
     * @var bool
     */
    static protected $freeze = false;

    /**
     * @var array
     */
    static protected $editorContentDividePosition;

    /**
     * ..
     */
    static function clearCache()
    {
        self::$cachedEditors = [];
    }

    /**
     * @param $path
     * @param null $param
     */
    static function refresh($path, $param = null)
    {
        $hash = FileUtils::hashName($path);
        $info = static::$openedFiles[$hash];

        if (!$info) {
            static::open($path, false, $param);
            return;
        }
    }

    /**
     * @param $path
     * @param null $param
     * @return AbstractEditor|null
     */
    static function openOrRefresh($path, $param = null)
    {
        $hash = FileUtils::hashName($path);
        $info = static::$openedFiles[$hash];

        if (!$info) {
            return static::open($path, true, $param);
        } else {
            $editor = static::getOpenedEditor($path);

            if (self::getSelectedEditor() !== $editor) {
                $tab = null;

                foreach (self::getOpenedTabs() as $tab) {
                    if ($tab->userData === $editor) {
                        self::$freeze = true;
                        Ide::get()->getMainForm()->{'fileTabPane'}->selectTab($tab);
                        self::$freeze = false;

                        break;
                    }
                }

                static::_openEditor($editor, $param);
            } else {
                $editor->open($param);
            }

            return $editor;
        }
    }

    /**
     * @return array
     */
    static function getOpened()
    {
        $result = [];

        foreach (static::getOpenedTabs() as $tab) {
            if ($tab->userData instanceof AbstractEditor) {
                $file = "{$tab->userData->getFile()}";

                $result[FileUtils::hashName($file)] = [
                    'file' => $file,
                    'mtime' => File::of($file)->lastModified(),
                ];
            }
        }

        return $result;
    }

    /**
     * @param $file
     * @return AbstractEditor
     */
    static function getOpenedEditor($file)
    {
        return static::$openedEditors[FileUtils::hashName($file)];
    }

    /**
     * @return UXTab[]
     */
    private static function getOpenedTabs()
    {
        /** @var UXTabPane $fileTabPane */
        $fileTabPane = Ide::get()->getMainForm()->{'fileTabPane'};

        return items::toArray($fileTabPane->tabs);
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    static function isOpened($path)
    {
        $hash = FileUtils::hashName($path);

        return isset(static::$openedFiles[$hash]);
    }

    /**
     * ...
     */
    static function saveAll()
    {
        foreach (static::$openedEditors as $editor) {
            if ($editor->isCorrectFormat()) {
                $editor->save();
            }
        }
    }

    /**
     * @return null|string
     */
    static function getSelected()
    {
        $editor = static::getSelectedEditor();

        if ($editor) {
            return $editor->getFile();
        }

        return null;
    }

    /**
     * @return AbstractEditor|null
     */
    static function getSelectedEditor()
    {
        /** @var UXTabPane $fileTabPane */
        $mainForm = Ide::get()->getMainForm();

        if (!$mainForm) {
            return null;
        }

        $fileTabPane = $mainForm->{'fileTabPane'};

        if (!$fileTabPane) {
            return null;
        }

        $tab = $fileTabPane->selectedTab;

        if ($tab && $tab->userData instanceof AbstractEditor) {
            return $tab->userData;
        }

        return null;
    }

    /**
     * @param $path
     * @param bool $cache
     * @return AbstractEditor|null
     */
    static function fetchEditor($path, $cache = false)
    {
        $hash = FileUtils::hashName($path);

        if ($editor = static::$openedEditors[$hash]) {
            return $editor;
        }

        if ($cache && ($editor = static::$cachedEditors[$hash])) {
            return $editor;
        }

        $editor = Ide::get()->createEditor($path);

        if (!$editor) {
            if (!fs::exists($path)) {
                Logger::error("Unable fetch editor, $path is not found");
            }

            return null;
        }

        $editor->load();

        static::$cachedEditors[$hash] = $editor;

        return $editor;
    }

    static public function _openEditor($editor, $param = null)
    {
        /** @var MainForm $mainForm */
        $mainForm = Ide::get()->getMainForm();

        if ($editor instanceof AbstractEditor && $editor !== static::$previousEditor) {
            if (static::$previousEditor) {
                static::$previousEditor->hide();
            }

            /*$mainForm->clearLeftPane();
            $mainForm->setLeftPane($editor->getLeftPaneUi());*/

            $editor->open($param);

            static::$previousEditor = $editor;
        } else {
            $previousEditor = null;
        }

        $project = Ide::project();

        if ($project) {
            $project->update();
        }
    }

    /**
     * @param $path
     * @param bool $switchToTab
     * @param null $param
     * @return AbstractEditor|null
     * @throws \php\lang\IllegalStateException
     */
    static function open($path, $switchToTab = true, $param = null)
    {
        if ($path instanceof AbstractEditor) {
            $path = $path->getFile();
        }

        if (Ide::project() && fs::exists($path)) {
            $path = Ide::project()->getAbsoluteFile($path);
        }

        $hash = FileUtils::hashName($path);

        $editor = static::$openedEditors[$hash];
        $tab    = static::$openedTabs[$hash];
        $info   = (array) static::$openedFiles[$hash];

        if (!$editor) {
            $editor = self::fetchEditor($path);

            if (!$editor) {
                return null;
            }

            $info['file'] = $path;
            $info['mtime'] = File::of($path)->lastModified();
        }

        if ($editor->isIncorrectFormat()) {
            Ide::get()->getMainForm()->toast("Ошибка загрузки данных, некорректный или поврежденный файл.\n\n{$path}");
            return null;
        }

        if (!$tab) {
            $tab = new UXTab();

            $tab->text = $editor->getTitle();
            $tab->tooltip = $editor->getTooltip();
            $tab->style = $editor->getTabStyle();
            $tab->graphic = Ide::get()->getImage($editor->getIcon());

            $content = $editor->makeUi();

            if ($leftPaneUi = $editor->makeLeftPaneUi()) {
                $editor->setLeftPaneUi($leftPaneUi);

                if ($leftPaneUi instanceof IdeTabPane) {
                    $leftPaneUi = $leftPaneUi->makeUi();
                }

                $wrapScroll = new UXScrollPane($leftPaneUi);
                $wrapScroll->fitToHeight = true;
                $wrapScroll->fitToWidth = true;
                UXAnchorPane::setAnchor($wrapScroll, 0);

                $wrap = new UXAnchorPane();
                $wrap->width = 250;
                $wrap->add($wrapScroll);
                UXSplitPane::setResizeWithParent($wrap, false);

                $content = new UXSplitPane([$wrap, $content]);

                $init = false;

                if (static::$editorContentDividePosition) {
                    $init = true;
                    $content->dividerPositions = static::$editorContentDividePosition;
                }


                $wrap->observer('width')->addListener(function () use (&$dividePositions, $content) {
                    static::$editorContentDividePosition = $content->dividerPositions;
                });

                $content->observer('width')->addListener(function ($_, $width) use (&$init, $content, $wrap) {
                    if (!$init) {
                        $init = true;
                        $content->dividerPositions = [$wrap->width/$width];
                    }
                });
            } else {
                $editor->setLeftPaneUi(null);
            }

            $tab->content = $content;

            $tab->userData = $editor;

            $tab->closable = $editor->isCloseable();

            /** @var MainForm $mainForm */
            $mainForm = Ide::get()->getMainForm();

            $tab->on('closeRequest', function (UXEvent $e) use ($path, $editor) {
                /*if (static::$previousEditor === $e->sender->userData) {
                    uiLater(function () {
                        if (Ide::project()) {
                            FileSystem::open(Ide::project()->getMainProjectFile());
                        }
                    });
                }*/

                static::close($path, false);
            });

            $changeHandler = function (UXEvent $e = null, $param = null) use ($mainForm, $path) {
                /** @var UXTabPane $fileTabPane */
                $fileTabPane = Ide::get()->getMainForm()->{'fileTabPane'};

                if ($e && $e->sender === $fileTabPane->selectedTab) {
                    return;
                }

                if (static::$freeze) {
                    return;
                }

                uiLater(function () use ($mainForm, $path, $fileTabPane, $param) {
                    $tab = $fileTabPane->selectedTab;

                    if ($tab) {
                        if (static::$editorContentDividePosition && $tab->content instanceof UXSplitPane) {
                            $tab->content->dividerPositions = static::$editorContentDividePosition;
                        }

                        Logger::info("Opening selected tab '$tab->text'");

                        static::_openEditor($tab->userData, $param);
                    }
                });
            };

            uiLater(function () use ($tab, $changeHandler) {
                $tab->data('change-handler', $changeHandler);
                $tab->on('change', $changeHandler);
            });

            if ($switchToTab) {
                $changeHandler(null, $param);
            }

            static::addTab($tab);
            $tab->draggable = $editor->isDraggable();
        } else {
            if (static::$editorContentDividePosition && $tab->content instanceof UXSplitPane) {
                $tab->content->dividerPositions = static::$editorContentDividePosition;
            }
        }

        if ($switchToTab) {
            Ide::get()->getMainForm()->{'fileTabPane'}->selectTab($tab);
        }

        static::$openedFiles[$hash] = $info;
        static::$openedTabs[$hash] = $tab;
        static::$openedEditors[$hash] = $editor;

        return $editor;
    }

    static function closeAllTabs()
    {
        Ide::get()->getMainForm()->{'fileTabPane'}->tabs->clear();
    }

    static function close($path, $removeTab = true, $save = true)
    {
        $hash = FileUtils::hashName($path);

        /** @var AbstractEditor $editor */
        $editor = static::$openedEditors[$hash];
        $tab    = static::$openedTabs[$hash];

        unset(static::$openedTabs[$hash], static::$openedEditors[$hash], static::$openedFiles[$hash], static::$cachedEditors[$hash]);

        if ($editor) {
            $editor->close($editor->isCorrectFormat() && $save);
        }

        if ($removeTab && $tab) {
            Ide::get()->getMainForm()->{'fileTabPane'}->tabs->remove($tab);
        }
    }

    static function setClickOnAddTab(callable $callback = null)
    {
        self::$addTabClick = $callback;

        if (self::$addTab) {
            if ($callback) {
                self::showAddTab();
            } else {
                self::hideAddTab();
            }
        }
    }

    private static function addTab(UXTab $tab)
    {
        /** @var UXTabPane $fileTabPane */
        $fileTabPane = Ide::get()->getMainForm()->{'fileTabPane'};

        static::hideAddTab();
        $fileTabPane->tabs->add($tab);
        static::showAddTab();
    }

    private static function hideAddTab()
    {
        if (static::$addTab) {
            /** @var UXTabPane $fileTabPane */
            $fileTabPane = Ide::get()->getMainForm()->{'fileTabPane'};

            $fileTabPane->tabs->remove(static::$addTab);
        }
    }

    private static function showAddTab()
    {
        /** @var UXTabPane $fileTabPane */
        $fileTabPane = Ide::get()->getMainForm()->{'fileTabPane'};

        if (!static::$addTab) {
            $tab = new UXTab();
            $tab->draggable = false;
            $tab->closable = false;
            $tab->style = '-fx-cursor: hand; -fx-padding: 1px 0px;';
            $tab->graphic = Ide::get()->getImage('icons/plus16.png');

            $button = new UXButton();
            $tab->graphic = $button;

            $button->graphic = Ide::get()->getImage('icons/plus16.png');
            $button->classes->add('dn-add-tab-button');
            $button->style = '-fx-background-radius: 0; -fx-border-radius: 0; -fx-border-width: 0';

            /*$showMenu = function (UXMouseEvent $event) use ($button) {
                call_user_func(self::$addTabClick);

                $contextMenu = new UXContextMenu();

                // @var UXMenu $menu
                $menu = Ide::get()->getMainForm()->findSubMenu('menuCreate');

                foreach ($menu->items as $item) {
                    $contextMenu->items->add($item);
                }

                $contextMenu->showByNode($button, 24, 24);
            };*/
            $button->on('click', function ($e) {
                call_user_func(self::$addTabClick, $e);
            });

            static::$addTab = $tab;
        }

        if (self::$addTabClick) {
            $fileTabPane->tabs->add(static::$addTab);
            static::$addTab->draggable = false;
        }
    }

    /**
     * Open next tab (it's for Ctrl + Tab)
     */
    public static function openNext()
    {
        Logger::info("Open next...");

        /** @var UXTabPane $fileTabPane */
        $fileTabPane = Ide::get()->getMainForm()->{'fileTabPane'};

        $index = $fileTabPane->selectedIndex;

        if ($index >= $fileTabPane->tabs->count-1 || (self::$addTab && $index >= $fileTabPane->tabs->count-2)) {
            $index = 0;
        } else {
            $index += 1;
        }

        $nextTab = $fileTabPane->tabs[$index];

        if ($nextTab && $nextTab->userData instanceof AbstractEditor) {
            static::open($nextTab->userData->getFile());
        } else {
            Logger::warn("Unable to open next tab, index = $index, tab = $nextTab");
        }
    }
}