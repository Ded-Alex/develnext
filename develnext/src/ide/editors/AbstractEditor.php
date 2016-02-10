<?php
namespace ide\editors;
use ide\editors\form\IdeTabPane;
use ide\formats\AbstractFormat;
use ide\Ide;
use ide\Logger;
use ide\project\Project;
use ide\project\ProjectIndexer;
use ide\systems\FileSystem;
use php\gui\layout\UXPane;
use php\gui\UXNode;
use php\lang\IllegalStateException;
use php\lang\Thread;
use php\lib\str;

/**
 * Class AbstractEditor
 * @package ide\editors
 */
abstract class AbstractEditor
{
    /** @var string */
    protected $file;

    /**
     * @var AbstractFormat
     */
    protected $format;

    /**
     * @var null|IdeTabPane
     */
    protected $leftPaneUi = null;


    public $cacheData = [];

    /**
     * AbstractEditor constructor.
     * @param string $file
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * @return \php\util\Configuration
     */
    protected function getIdeConfig()
    {
        if (Ide::project()) {
            $file = Ide::project()->getAbsoluteFile($this->file);

            if ($file->isInRootDir()) {
                $name = str::replace(get_class($this), "\\", "/") . "/" . $file->getRelativePath() . ".conf";

                return Ide::project()->getIdeConfig($name);
            }

            return null;
        } else {
            return null;
        }
    }

    protected function saveIdeConfig()
    {
        if (Ide::project()) {
            $file = Ide::project()->getAbsoluteFile($this->file);

            if ($file->isInRootDir()) {
                $name = str::replace(get_class($this), "\\", "/") . "/" . $file->getRelativePath() . ".conf";

                Ide::project()->saveIdeConfig($name);
            }
        }
    }

    /**
     * @return bool
     */
    public function isCloseable()
    {
        return true;
    }

    public function isDraggable()
    {
        return true;
    }

    public function getTabStyle()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param AbstractFormat $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @return AbstractFormat
     */
    public function getFormat()
    {
        return $this->format;
    }

    protected function reindexImpl(ProjectIndexer $indexer)
    {
        // nop.
    }

    public function reindex()
    {
        $project = Ide::get()->getOpenedProject();

        if ($project) {
            $this->reindexImpl($project->getIndexer());
        }
    }

    abstract public function load();
    abstract public function save();

    public function close()
    {
        $this->save();
        $this->reindex();
    }

    public function open()
    {
        Logger::info("Open editor for: $this->file");
        $this->reindex();
    }

    public function refresh()
    {
        // nop
    }

    public function getTitle()
    {
        return $this->format->getTitle($this->file);
    }

    public function getIcon()
    {
        return $this->format->getIcon();
    }

    public function getTooltip()
    {
        return null;
    }

    /**
     * @return UXNode
     */
    abstract public function makeUi();

    /**
     * Контент для левой панели главной формы Ide.
     *
     * @return IdeTabPane|UXNode
     */
    public function makeLeftPaneUi()
    {
        return null;
    }

    /**
     * @return null
     */
    public function getLeftPaneUi()
    {
        return $this->leftPaneUi;
    }

    /**
     * @param null $leftPaneUi
     * @throws IllegalStateException
     */
    public function setLeftPaneUi($leftPaneUi)
    {
        if ($this->leftPaneUi) throw new IllegalStateException();

        $this->leftPaneUi = $leftPaneUi;
    }

    public function hide()
    {
        ;
    }

    public function delete($silent = false)
    {
        FileSystem::close($this->file);

        $this->getFormat()->delete($this->file, $silent);
    }
}