<?php
namespace ide\editors;

use ide\action\ActionEditor;
use ide\behaviour\IdeBehaviourManager;
use ide\editors\common\ObjectListEditorItem;
use ide\editors\form\IdeBehaviourPane;
use ide\editors\form\FormElementTypePane;
use ide\editors\form\IdeEventListPane;
use ide\editors\form\IdeObjectTreeList;
use ide\editors\form\IdePropertiesPane;
use ide\editors\form\IdeTabPane;
use ide\editors\menu\ContextMenu;
use ide\formats\AbstractFormFormat;
use ide\formats\form\AbstractFormDumper;
use ide\formats\form\AbstractFormElement;
use ide\formats\form\elements\FormFormElement;
use ide\formats\form\event\AbstractEventKind;
use ide\formats\form\SourceEventManager;
use ide\formats\FormFormat;
use ide\formats\PhpCodeFormat;
use ide\forms\ActionConstructorForm;
use ide\forms\MainForm;
use ide\forms\MessageBoxForm;
use ide\Ide;
use ide\Logger;
use ide\misc\AbstractCommand;
use ide\project\behaviours\GuiFrameworkProjectBehaviour;
use ide\project\ProjectFile;
use ide\project\ProjectIndexer;
use ide\systems\FileSystem;
use ide\utils\FileUtils;
use php\gui\designer\UXDesigner;
use php\gui\designer\UXDesignPane;
use php\gui\designer\UXDesignProperties;
use php\gui\event\UXEvent;
use php\gui\event\UXMouseEvent;
use php\gui\framework\AbstractForm;
use php\gui\framework\DataUtils;
use php\gui\framework\Timer;
use php\gui\layout\UXAnchorPane;
use php\gui\layout\UXHBox;
use php\gui\layout\UXPane;
use php\gui\layout\UXScrollPane;
use php\gui\layout\UXVBox;
use php\gui\paint\UXColor;
use php\gui\text\UXFont;
use php\gui\UXApplication;
use php\gui\UXButton;
use php\gui\UXContextMenu;
use php\gui\UXData;
use php\gui\UXDialog;
use php\gui\UXForm;
use php\gui\UXHyperlink;
use php\gui\UXImage;
use php\gui\UXImageView;
use php\gui\UXLabel;
use php\gui\UXList;
use php\gui\UXListCell;
use php\gui\UXListView;
use php\gui\UXLoader;
use php\gui\UXMenu;
use php\gui\UXMenuItem;
use php\gui\UXNode;
use php\gui\UXParent;
use php\gui\UXPopupWindow;
use php\gui\UXSplitPane;
use php\gui\UXTab;
use php\gui\UXTabPane;
use php\gui\UXTextArea;
use php\gui\UXTextField;
use php\gui\UXTooltip;
use php\gui\UXWebView;
use php\io\File;
use php\io\Stream;
use php\lang\IllegalStateException;
use php\lib\Items;
use php\lib\Str;
use php\lib\String;
use php\time\Time;
use php\util\Configuration;
use php\util\Flow;
use php\util\Regex;

/**
 * Class FormEditor
 * @package ide\editors
 *
 * @property AbstractFormFormat $format
 */
class FormEditor extends AbstractModuleEditor
{
    const BORDER_SIZE = 8;

    protected $designerCodeEditor;

    /**
     * @var IdePropertiesPane
     */
    protected $propertiesPane;

    /** @var  UXHBox */
    protected $modulesPane;

    /** @var UXSplitPane */
    protected $viewerAndEvents;

    /** @var UXTab */
    protected $designerTab, $codeTab;

    /** @var UXTabPane */
    protected $tabs;

    /**
     * @var string
     */
    protected $codeFile;

    /**
     * @var string
     */
    protected $configFile;

    /**
     * @var UXPane
     */
    protected $layout;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var UXDesigner
     */
    protected $designer;

    /**
     * @var FormElementTypePane
     */
    protected $elementTypePane;

    /**
     * @var ContextMenu
     */
    protected $contextMenu;

    /**
     * @var AbstractFormDumper
     */
    protected $formDumper;

    /**
     * @var CodeEditor
     */
    protected $codeEditor;

    /**
     * @var ActionEditor
     */
    protected $actionEditor;

    /**
     * @var IdeBehaviourManager
     */
    protected $behaviourManager;

    /**
     * @var string
     */
    protected $tabOpened = null;

    /**
     * @var SourceEventManager
     */
    protected $eventManager;

    /**
     * @var ScriptModuleEditor[]
     */
    protected $modules = [];

    /**
     * @var UXDesignProperties[]
     */
    protected static $typeProperties = [];

    protected $opened;

    /**
     * @var UXNode
     */
    protected $codeEditorUi;

    /**
     * @var IdeEventListPane
     */
    protected $eventListPane;

    /**
     * @var IdeBehaviourPane
     */
    protected $behaviourPane;

    /**
     * @var IdeObjectTreeList
     */
    protected $objectTreeList;

    public function __construct($file, AbstractFormDumper $dumper)
    {
        parent::__construct($file);

        $this->config = new Configuration();
        $this->formDumper = $dumper;

        $phpFile = $file;
        $confFile = $file;

        if (Str::endsWith($phpFile, '.fxml')) {
            $phpFile = Str::sub($phpFile, 0, Str::length($phpFile) - 5);
            $confFile = $phpFile;
        }

        $phpFile  .= '.php';
        $confFile .= '.conf';

        if ($file instanceof ProjectFile) {
            if ($link = $file->findLinkByExtension('php')) {
                $phpFile = $link;
            }

            if ($link = $file->findLinkByExtension('conf')) {
                $confFile = $link;
            }
        }

        $this->eventManager = new SourceEventManager($phpFile);

        $this->codeFile = $phpFile;
        $this->configFile = $confFile;

        $this->initCodeEditor($this->codeFile);

        $this->actionEditor = new ActionEditor($phpFile . '.axml');
        $this->actionEditor->setFormEditor($this);

        $this->behaviourManager = new IdeBehaviourManager(FileUtils::stripExtension($phpFile) . '.behaviour', function ($targetId) {
            return $this->getFormat()->getFormElement($targetId ? $this->layout->lookup("#$targetId") : $this);
        });
    }

    protected function initCodeEditor($phpFile)
    {
        $this->codeEditor = Ide::get()->getRegisteredFormat(PhpCodeFormat::class)->createEditor($phpFile);
        $this->codeEditor->register(AbstractCommand::make('Скрыть', 'icons/close16.png', function () {
            $this->codeEditor->save();
            $this->switchToDesigner(true);
        }));
        $this->codeEditor->register(AbstractCommand::make('Поменять расположение', 'icons/layoutHorizontal16.png', function () {
            $this->viewerAndEvents->orientation = $this->viewerAndEvents->orientation == 'VERTICAL' ? 'HORIZONTAL' : 'VERTICAL';
        }));

        $this->codeEditor->register(AbstractCommand::makeSeparator());

        $this->codeEditor->registerDefaultCommands();
        $this->codeEditor->register(new SetDefaultCommand($this, 'php'));

        $this->codeEditor->on('update', function () {
            if ($this->opened) {
                $node = $this->designer->pickedNode;
                $this->codeEditor->save();

                $this->updateEventTypes($node ? $node : $this);
            }
        });
    }

    public function getTooltip()
    {
        $tooltip = new UXTooltip();
        $tooltip->text = (new File($this->file))->getPath();

        return $tooltip;
    }

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param UXPane $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * @return UXPane
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @return AbstractFormDumper
     */
    public function getFormDumper()
    {
        return $this->formDumper;
    }

    /**
     * @return UXDesigner
     */
    public function getDesigner()
    {
        return $this->designer;
    }

    /**
     * @return IdeBehaviourManager
     */
    public function getBehaviourManager()
    {
        return $this->behaviourManager;
    }

    protected function loadOthers()
    {
        $this->actionEditor->load();
        $this->behaviourManager->load();

        if (File::of($this->codeFile)->exists()) {
            $this->codeEditor->load();
        }
    }

    /**
     * @return UXNode
     */
    public function load()
    {
        $this->eventManager->load();
        $this->formDumper->load($this);

        $this->loadOthers();

        if (File::of($this->configFile)->exists()) {
            $this->config->load($this->configFile);
        }

        if ($this->config->get('form.backgroundColor')) {
            $this->layout->backgroundColor = UXColor::of($this->config->get('form.backgroundColor'));
        }
    }

    protected function saveOthers()
    {
        if (File::of($this->codeFile)->exists()) {
            $this->codeEditor->save();
        }

        $this->actionEditor->save();
        $this->behaviourManager->save();
    }

    public function save()
    {
        $this->formDumper->save($this);

        $this->saveOthers();

        Stream::tryAccess($this->configFile, function (Stream $stream) {
            $this->config->save($stream);
        }, 'w+');
    }

    public function close()
    {
        parent::close();

        $this->opened = false;

        if (FileSystem::getOpened() === $this) {
            $this->updateProperties(null);
        }
    }

    public function open()
    {
        parent::open();

        $this->designer->disabled = false;
        $this->opened = true;
        //$this->designer->unselectAll();

        $this->eventManager->load();

        $this->refresh();
        $this->leftPaneUi->refresh();
        $this->leftPaneUi->refreshObjectTreeList();

        UXApplication::runLater(function () {
            $this->updateProperties($this->designer->pickedNode ?: $this);
            //$this->updateEventTypes($this->designer->pickedNode ?: $this);

            UXApplication::runLater(function () {
                $this->designer->requestFocus();
            });
        });
    }

    public function hide()
    {
        parent::hide();

        $this->designer->disabled = true;
        $this->reindex();
    }

    public function refreshNode(UXNode $node)
    {
        $element = $this->format->getFormElement($node);

        if ($element) {
            $element->refreshNode($node);
        }
    }

    public function refresh()
    {
        Logger::info("Start refresh");

        $this->eachNode(function (UXNode $node, $nodeId, AbstractFormElement $element = null) {
            if ($element) {
                $element->refreshNode($node);
            }
        });

        Logger::info("Finish refresh");
    }

    public function registerNode(UXNode $uiNode)
    {
        $element = $this->format->getFormElement($uiNode);

        if ($element) {
            if ($new = $element->registerNode($uiNode)) {
                $uiNode = $new;
            }
        }

        $this->designer->registerNode($uiNode);

        return $uiNode;
    }

    protected function reindexImpl(ProjectIndexer $indexer)
    {
        if (!$this->layout) {
            $this->formDumper->load($this);
        }

        $nodes = $this->findNodesToRegister($this->layout->children);

        $result = [];

        $indexer->remove($this->file, '_objects');

        $index = [];

        foreach ($nodes as $node) {
            $element = $this->format->getFormElement($node);

            $index[$this->getNodeId($node)] = [
                'id' => $this->getNodeId($node),
                'type' => get_class($element),
            ];
        }

        $indexer->set($this->file, '_objects', $index);

        return $result;
    }


    public function checkNodeId($newId)
    {
        return (Regex::match('^[A-Za-z\\_]{1}[A-Za-z0-9\\_]{1,60}$', $newId));
    }

    public function changeNodeId($node, $newId)
    {
        if (!$this->checkNodeId($newId)) {
            return 'invalid';
        }

        if ($node->id == $newId) {
            return '';
        }

        if ($this->layout->lookup("#$newId")) {
            return 'busy';
        }

        $data = DataUtils::get($node, $this->layout, false);

        if ($data) {
            $data->id = "data-$newId";
        }

        $this->behaviourManager->changeTargetId($node->id, $newId);

        $binds = $this->eventManager->renameBind($node->id, $newId);

        foreach ($binds as $bind) {
            $this->actionEditor->renameMethod($bind['className'], $bind['methodName'], $bind['newMethodName']);
        }

        $this->codeEditor->load();
        $this->reindex();

        $node->id = $newId;

        $this->leftPaneUi->updateEventList($newId);
        $this->leftPaneUi->updateBehaviours($newId);
        $this->leftPaneUi->refreshObjectTreeList($newId);
        return '';
    }

    /**
     * @return ObjectListEditorItem[]
     */
    public function getObjectList()
    {
        $project = Ide::get()->getOpenedProject();
        $result = [];

        if ($project) {

            $index = $project->getIndexer()->get($this->file, '_objects');

            foreach ((array)$index as $it) {
                /** @var AbstractFormElement $element */
                $element = class_exists($it['type']) ? new $it['type']() : null;

                $result[] = $item = new ObjectListEditorItem(
                    $it['id'], $element ? Ide::get()->getImage($element->getIcon()) : null
                );

                $item->hint = $element ? $element->getName() : '';
                $item->element = $element;
            }
        }

        return $result;
    }

    public function deleteNode($node)
    {
        $designer = $this->designer;

        $element = $this->format->getFormElement($node);

        if ($element && $element->isLayout()) {
            foreach ($element->getLayoutChildren($node) as $sub) {
                $this->deleteNode($sub);
            }
        }

        $designer->unselectNode($node);
        $designer->unregisterNode($node);

        DataUtils::remove($node);
        $node->parent->remove($node);

        $nodeId = $this->getNodeId($node);

        $binds = $this->eventManager->findBinds($nodeId);

        foreach ($binds as $bind) {
            $this->actionEditor->removeMethod($bind['className'], $bind['methodName']);
        }

        if ($this->eventManager->removeBinds($nodeId)) {
            $this->codeEditor->load();
        }

        $this->behaviourManager->removeBehaviours($nodeId);
        $this->behaviourManager->save();

        $this->leftPaneUi->refreshObjectTreeList();
        $this->reindex();
    }

    public function selectForm()
    {
        $this->designer->unselectAll();

        $this->updateProperties($this);
    }

    public function selectObject($targetId)
    {
        $node = $this->layout->lookup("#$targetId");

        $this->designer->unselectAll();

        if ($node) {
            $this->designer->selectNode($node);

            Timer::run(50, function () use ($node) {
                $this->updateProperties($node);
            });
        }
    }

    public function makeUi()
    {
        if (!$this->layout) {
            throw new \Exception("Cannot open unloaded form");
        }

        $this->codeEditorUi = $codeEditor = $this->makeCodeEditor();
        $designer = $this->makeDesigner();

        $tabs = new UXTabPane();
        $tabs->side = 'LEFT';
        $tabs->tabClosingPolicy = 'UNAVAILABLE';

        $codeTab = new UXTab();
        $codeTab->text = 'Исходный код';
        $codeTab->style = '-fx-cursor: hand;';
        $codeTab->graphic = Ide::get()->getImage($this->codeEditor->getIcon());
        $codeTab->tooltip = UXTooltip::of($this->codeFile);

        $designerTab = new UXTab();
        $designerTab->text = 'Дизайн';
        $designerTab->content = $designer;
        $designerTab->style = '-fx-cursor: hand;';
        $designerTab->graphic = Ide::get()->getImage($this->getIcon());

        $this->designerTab = $designerTab;

        $tabs->tabs->add($this->designerTab);

        $this->codeTab = $codeTab;

        if (File::of($this->codeFile)->exists()) {
            $codeTab->on('change', function () use ($tabs) {
                UXApplication::runLater(function () use ($tabs) {
                    $tabs->selectedTab = $this->designerTab;
                });
                Timer::run(300, function () use ($tabs) {
                    //if ($tabs->selectedTab === $this->codeTab) {
                        $this->switchToSmallSource();
                        //$tabs->selectedTab = $this->designerTab;
                    //}
                });
            });

            $tabs->tabs->add($this->codeTab);
        }

        $this->tabs = $tabs;

        if (Ide::get()->getUserConfigValue(__CLASS__ . '.sourceEditorEx', false)) {
            UXApplication::runLater(function () {
                $this->switchToSmallSource();
            });
        }

        return $this->tabs;
    }

    public function switchToSource()
    {
        $this->tabs->selectTab($this->codeTab);
    }

    public function switchToSmallSource()
    {
        static $dividerPositions;

        Logger::info("Start switch to small source editor...");

        $data = Ide::get()->getUserConfigValue(__CLASS__ . ".dividerPositions");

        if ($data) {
            $dividerPositions = Flow::of(Str::split($data, ','))->map(function ($el) {
                return (double) Str::trim($el);
            })->toArray();
        }

        UXApplication::runLater(function () { // FIX LOCK EDITOR
            $this->switchToDesigner();
            $this->codeEditor->requestFocus();
        });

        $count = $this->viewerAndEvents->items->count();

        if ($count > 1) {
            $dividerPositions = $this->viewerAndEvents->dividerPositions;

            $item = $this->viewerAndEvents->items[$count - 1];
            $this->viewerAndEvents->items->remove($item);

            Logger::info(".. reset small code editor");
        }

        $panel = new UXAnchorPane();

        // Странный фикс плавающего лока редактора кода, но он работает 0_0
        $field = new UXTextField('');
        $field->y = 50;
        $panel->add($field);
        // --------------------------

        $content = $this->codeEditorUi;
        UXAnchorPane::setAnchor($content, 0);

        $panel->add($content);

        if ($dividerPositions) {
            $this->viewerAndEvents->dividerPositions = $dividerPositions;
        }

        $class = __CLASS__;

        $func = function () use ($class) {
            UXApplication::runLater(function () use ($class) {
                if ($this->viewerAndEvents->items->count() > 1) {
                    Ide::get()->setUserConfigValue("$class.dividerPositions", Str::join($this->viewerAndEvents->dividerPositions, ','));
                }
            });
        };

        $this->viewerAndEvents->items->add($panel);

        $panel->observer('width')->addListener($func);
        $panel->observer('height')->addListener($func);

        Ide::get()->setUserConfigValue("$class.sourceEditorEx", true);

        Logger::info("Finish switching of small source editor");
    }

    public function getModules()
    {
        $modules = $this->config->get('modules');

        $modules = Str::split($modules, '|');

        $result = [];

        foreach ($modules as &$module) {
            $module = Str::trim($module);
            $result[$module] = $module;
        }

        return $result;
    }

    public function getModuleEditors()
    {
        $modules = $this->getModules();

        foreach ($this->modules as $name => $value) {
            if (!$modules[$name]) {
                unset($this->modules[$name]);
            }
        }

        foreach ($modules as $module) {
            $module = Str::trim($module);

            if ($this->modules[$module]) {
                $this->modules[$module]->load();
            } else {
                $this->modules[$module] = FileSystem::fetchEditor(Ide::get()->getOpenedProject()->getFile(GuiFrameworkProjectBehaviour::SCRIPTS_DIRECTORY . "/$module"));
                $this->modules[$module]->makeUi();
            }
        }

        return $this->modules;
    }

    public function switchToDesigner($hideSource = false)
    {
        $this->tabs->selectTab($this->designerTab);

        if ($hideSource && $this->viewerAndEvents->items->count() > 1) {
            $class = __CLASS__;

            Ide::get()->setUserConfigValue("$class.sourceEditorEx", false);
            $this->codeTab->content = $this->viewerAndEvents->items[1];
            unset($this->viewerAndEvents->items[1]);
        }
    }

    protected function makeCodeEditor()
    {
        return $this->codeEditor->makeUi();
    }

    /**
     * @param callable $callback (UXNode $node, AbstractFormElement $element, int $level)
     */
    public function eachNode(callable $callback)
    {
        $func = function ($nodes, $level = 0) use ($callback, &$func) {
            foreach ($nodes as $node) {
                if ($node instanceof UXData || $node->classes->has('ignore')) {
                    continue;
                }

                $nodeId = $this->getNodeId($node);

                if (!$nodeId) {
                    continue;
                }

                $element = $this->format->getFormElement($node);

                $callback($node, $nodeId, $element, $level);

                if ($element && $element->isLayout()) {
                    $func($element->getLayoutChildren($node), $level + 1);
                }
            }
        };

        $func($this->layout->children);
    }

    protected function findNodesToRegister($nodes)
    {
        $result = [];

        $registerChildren = function ($children, &$result) use (&$registerChildren) {
            /** @var UXNode $node */
            foreach ($children as $node) {
                if (!$node) {
                    continue;
                }

                if ($node instanceof UXData) {
                    continue;
                }

                $targetId = $this->getNodeId($node);

                if (!$targetId) {
                    continue;
                }

                if (!$node->classes->has('ignore')) {
                    $element = $this->format->getFormElement($node);

                    if ($element && $node->id) {
                        if ($new = $element->registerNode($node)) {
                            $node = $new;
                        }
                    }

                    if ($element && $element->isLayout()) {
                        $registerChildren($element->getLayoutChildren($node), $result);
                    }

                    $result[] = $node;
                }
            }
        };

        $registerChildren($nodes, $result);
        return $result;
    }

    protected function makeDesigner($fullArea = false)
    {
        $area = new UXAnchorPane();
        $this->layout->classes->add('form-editor');

        $viewer = new UXScrollPane($area);

        /*$viewer->on('click', function ($e) {
            $this->designer->unselectAll();
            $this->_onAreaMouseUp($e);
        }); */

        if (!$fullArea) {
            $designPane = new UXDesignPane();
            $designPane->size = $this->layout->size;
            $designPane->position = [10, 10];
            $designPane->onResize(function () {
                $this->designer->update();
            });

            $designPane->add($this->layout);

            UXAnchorPane::setAnchor($this->layout, 0);
        } else {
            $this->layout->style = '-fx-border-width: 1px; -fx-border-style: none; -fx-border-color: silver;';
            $this->layout->position = [10, 10];
            $area->add($this->layout);
        }

        $this->designer = new UXDesigner($this->layout);
        $this->designer->onAreaMouseUp(function ($e) { $this->_onAreaMouseUp($e); } );
        $this->designer->onNodeClick([$this, '_onNodeClick']);
        $this->designer->onNodePick(function () {
            $this->_onNodePick();
        });

        $this->designer->onChanged([$this, '_onChanged']);

        foreach ($this->findNodesToRegister($this->layout->children) as $node) {
            $this->designer->registerNode($node);
        }

        if (!$fullArea) {
            $area->add($designPane);
        }

        $this->elementTypePane = new FormElementTypePane($this->format->getFormElements());
        //$this->behaviourPane = new IdeBehaviourPane($this->behaviourManager);

        $designerCodeEditor = new UXAnchorPane();
        $designerCodeEditor->hide();

        $this->designerCodeEditor = $designerCodeEditor;

        $class = __CLASS__;

        $this->viewerAndEvents = new UXSplitPane([$viewer, $this->designerCodeEditor]);

        try {
            $this->viewerAndEvents->orientation = Ide::get()->getUserConfigValue("$class.orientation", 'VERTICAL');
        } catch (\Exception $e) {
            $this->viewerAndEvents->orientation = 'VERTICAL';
        }

        $this->viewerAndEvents->watch('orientation', function () use ($class) {
            UXApplication::runLater(function () use ($class) {
                Ide::get()->setUserConfigValue("$class.orientation", $this->viewerAndEvents->orientation);
            });
        });

        $this->viewerAndEvents->items->remove($designerCodeEditor);

        $scrollPane = new UXScrollPane($this->elementTypePane->getContent());
        $scrollPane->fitToWidth = true;
        $scrollPane->maxWidth = $scrollPane->content->maxWidth;

        $split = new UXSplitPane([$this->viewerAndEvents, $scrollPane]);

        $this->makeContextMenu();

        return $split;
    }

    protected function makeContextMenu()
    {
        $this->contextMenu = new ContextMenu($this, $this->format->getContextCommands());
        $this->contextMenu->setFilter(function ()  {
            return $this->layout->focused || $this->contextMenu->getRoot()->visible || $this->layout->findFocusedNode();
        });

        $this->designer->contextMenu = $this->contextMenu->getRoot();
    }

    public function generateNodeId(AbstractFormElement $element)
    {
        $n = 3;

        $id = Str::format($element->getIdPattern(), "");

        if ($this->layout->lookup("#$id")) {
            $id = Str::format($element->getIdPattern(), "Alt");

            if ($this->layout->lookup("#$id")) {
                do {
                    $id = Str::format($element->getIdPattern(), $n);
                    $n++;
                } while ($this->layout->lookup("#$id"));
            }
        }

        return $id;
    }

    protected function createElement(AbstractFormElement $element, $screenX, $screenY, $parent = null)
    {
        Logger::info("Create element: element = " . get_class($element) . ", screenX = $screenX, screenY = $screenY, parent = $parent");

        $node = $element->createElement();

        if (!$node->id) {
            $node->id = $this->generateNodeId($element);
        }

        $size = $element->getDefaultSize();

        $selectionRectangle = $this->designer->getSelectionRectangle();

        if ($parent == null && $selectionRectangle->width >= 8 && $selectionRectangle->height >= 8) {
            $size = $selectionRectangle->size;
            $selectionRectangle->size = [1, 1];
        }

        $position = [$screenX, $screenY];

        $snapSize = $this->designer->snapSize;

        if ($this->designer->snapEnabled) {
            $size[0] = floor($size[0] / $snapSize) * $snapSize;
            $size[1] = floor($size[1] / $snapSize) * $snapSize;

            $position[0] = floor($position[0] / $snapSize) * $snapSize;
            $position[1] = floor($position[1] / $snapSize) * $snapSize;
        }

        $node->size = $size;

        if ($parent) {
            $parentElement = $this->format->getFormElement($parent);
            $parentElement->addToLayout($parent, $node, $screenX, $screenY);
        } else {
            $position = $this->layout->screenToLocal($screenX, $screenY);
            $node->position = $position;
            $this->layout->add($node);
        }

        $element = $this->format->getFormElement($node);

        $node = $this->registerNode($node);
        $data = DataUtils::get($node);

        foreach ($element->getInitProperties() as $key => $property) {
            if ($property['virtual']) {
                $data->set($key, $property['value']);
            } else if ($key !== 'width' && $key !== 'height') {
                $node->{$key} = $property['value'];
            }
        }

        $element->refreshNode($node);

        $this->reindex();
        $this->leftPaneUi->refreshObjectTreeList($this->getNodeId($node));

        return $node;
    }

    protected function _onAreaMouseUp(UXMouseEvent $e)
    {
        $selected = $this->elementTypePane->getSelected();

        $this->save();

        if ($selected) {
            $selectionRectangle = $this->designer->getSelectionRectangle();

            $node = $this->createElement($selected, $selectionRectangle->x, $selectionRectangle->y, null, !$e->controlDown);

            if (!$e->controlDown) {
                $this->elementTypePane->clearSelected();
            }

            $this->designer->requestFocus();

            UXApplication::runLater(function () use ($node) {
                $this->designer->unselectAll();
                $this->designer->selectNode($node);
            });
        } else {
            $this->updateProperties($this);
        }
    }

    public function addUseImports(array $imports)
    {
        $this->eventManager->addUseImports($imports);

        Timer::run(100, function () {
            $this->codeEditor->load();
        });
    }

    public function insertCodeToMethod($class, $method, $code)
    {
        $this->eventManager->insertCodeToMethod($class, $method, $code);

        Timer::run(100, function () {
            $this->codeEditor->load();
        });
    }

    protected function _onChanged()
    {
        $this->save();
        $this->_onNodePick();
    }

    protected function _onNodePick()
    {
        $node = $this->designer->pickedNode;

        if ($node) {
            Timer::run(50, function () use ($node) {
                $this->updateProperties($node);
            });
        }
    }

    protected function _onNodeClick(UXMouseEvent $e)
    {
        $selected = $this->elementTypePane->getSelected();

        $this->layout->requestFocus();

        if ($selected) {
            $element = $this->format->getFormElement($e->sender);

            if ($element) {
                $node = $this->createElement($selected, $e->screenX, $e->screenY, $element->isLayout() ? $e->sender : null, !$e->controlDown);

                if (!$e->controlDown) {
                    $this->elementTypePane->clearSelected();
                }

                $this->designer->requestFocus();

                UXApplication::runLater(function () use ($node) {
                    $this->designer->unselectAll();
                    $this->designer->selectNode($node);
                });
            }

            //$this->designer->unselectAll();
            $this->elementTypePane->clearSelected();
            return true;
        }
    }

    public static function initializeElement(AbstractFormElement $element)
    {
        Logger::info("Initialize element = " . get_class($element));

        $properties = new UXDesignProperties();
        $element->createProperties($properties);

        if (!$properties->getGroupPanes()) {
            Logger::warn("Properties is empty for element = " . get_class($element));
        }

        return static::$typeProperties[get_class($element)] = $properties;
    }

    public function makeLeftPaneUi()
    {
        $ui = new IdeTabPane();

        $objectTreeList = new IdeObjectTreeList();
        $objectTreeList->setTraverseFunc([$this, 'eachNode']);
        $objectTreeList->setLevelOffset(1);
        $objectTreeList->setEmptyItem(new ObjectListEditorItem(
            $this->getTitle(), Ide::get()->getImage($this->getIcon()), '', 0
        ));
        $objectTreeList->on('change', function ($targetId) {
            if ($targetId) {
                $this->selectObject($targetId);
            } else {
                $this->selectForm();
            }
        });

        $this->objectTreeList = $objectTreeList;
        $ui->addObjectTreeList($objectTreeList);

        $this->propertiesPane = new IdePropertiesPane();
        $ui->addPropertiesPane($this->propertiesPane);

        $this->eventListPane = new IdeEventListPane($this->eventManager);
        $this->eventListPane->setCodeEditor($this->codeEditor);
        $this->eventListPane->setActionEditor($this->actionEditor);
        $this->eventListPane->setContextEditor($this);
        $this->eventListPane->on('edit', function ($eventCode, $editor) {
            if ($editor == 'php') {
                $this->switchToSmallSource();
            }
        });

        $ui->addEventListPane($this->eventListPane);

        $this->behaviourPane = new IdeBehaviourPane($this->behaviourManager);
        $ui->addBehaviourPane($this->behaviourPane);

        return $ui;
    }

    protected function updateEventTypes($node, $selected = null)
    {
        $this->eventManager->load();
        $this->eventListPane->update($this->getNodeId($node));
    }

    protected function updateProperties($node)
    {
        $this->eventManager->load();

        $element = $this->format->getFormElement($node);
        $properties = $element ? static::$typeProperties[get_class($element)] : null;

        $this->propertiesPane->setProperties($properties);
        $this->eventListPane->setEventTypes($element ? $element->getEventTypes() : []);
        $this->leftPaneUi->update($this->getNodeId($node), $element ? $element->getTarget($node) : $node);
    }

    public function jumpToClassMethod($class, $method)
    {
        $coord = $this->eventManager->findMethod($class, $method);

        Logger::info("Jump to class method $class::$method()");

        if ($coord) {
            $this->switchToSmallSource();

            Timer::run(100, function () use ($coord) {
                $this->codeEditor->jumpToLine($coord['line'], $coord['pos']);
            });
        }
    }

    public function jumpToEventSource($node, $eventType)
    {
        $bind = $this->eventManager->findBind($this->getNodeId($node), $eventType);

        Logger::info("Jump to event source node = {$this->getNodeId($node)}, eventType = $eventType");

        if ($bind) {
            $this->switchToSmallSource();

            Timer::run(100, function () use ($bind) {
                $this->codeEditor->jumpToLine($bind['beginLine'], $bind['beginPosition']);
            });
        }
    }

    public function getNodeId($node)
    {
        return $node->id;
    }

    public function setDefaultEventEditor($editor)
    {
        Ide::get()->setUserConfigValue(CodeEditor::class . '.editorOnDoubleClick', $editor);
    }

    public function getDefaultEventEditor($request = true)
    {
        $editorType = Ide::get()->getUserConfigValue(CodeEditor::class . '.editorOnDoubleClick');

        if ($request && !$editorType) {
            $buttons = ['constructor' => 'Конструктор', 'php' => 'PHP редактор'];

            $dialog = new MessageBoxForm('Какой использовать редактор для редактирования событий?', $buttons);

            UXApplication::runLater(function () use ($dialog) {
                $dialog->toast('Используйте "Конструктор" если вы новичок!');
            });

            if ($dialog->showDialogWithFlag()) {
                $editorType = $dialog->getResult();

                if ($dialog->isChecked()) {
                    Ide::get()->setUserConfigValue(CodeEditor::class . '.editorOnDoubleClick', $editorType);
                }
            }
        }

        return $editorType;
    }
}