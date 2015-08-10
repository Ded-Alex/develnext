<?php
namespace php\gui\framework;

use Exception;
use php\gui\event\UXEvent;
use php\gui\UXApplication;
use php\gui\UXData;
use php\gui\UXForm;
use php\gui\UXLoader;
use php\gui\UXNode;
use php\gui\UXNodeWrapper;
use php\io\IOException;
use php\io\Stream;
use php\lang\IllegalStateException;
use php\lib\Items;
use php\lib\Str;
use php\lib\String;
use php\util\Configuration;
use php\util\Scanner;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class AbstractForm
 * @package php\gui\framework
 */
abstract class AbstractForm extends UXForm
{
    const DEFAULT_PATH = 'res://.forms/';

    /** @var Application */
    protected $_app;

    /** @var Configuration */
    protected $_config;

    /** @var AbstractModule[] */
    protected $_modules = [];

    /**
     * @param UXForm $origin
     * @throws Exception
     */
    public function __construct(UXForm $origin = null)
    {
        parent::__construct($origin);

        $this->_app = Application::get();
        $this->loadConfig(null, false);

        $this->loadDesign();
        $this->loadBindings($this);

        $this->applyConfig();

        $this->init();

        $this->addStylesheet('/php/gui/framework/style.css');

        if (Stream::exists('res://.theme/style.css')) {
            $this->addStylesheet('/.theme/style.css');
        }
    }

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->_config;
    }

    public function show()
    {
        parent::show();

        if ($this->_config && $this->_config->get('maximized')) {
            $this->maximized = true;
        }
    }

    protected function init()
    {
        // nop.
    }

    /**
     * @param $id
     * @return AbstractModule
     * @throws Exception
     */
    protected function module($id)
    {
        $module = $this->_modules[$id];

        if (!$module) {
            throw new Exception("Unable to find '$id' module");
        }

        return $module;
    }

    protected function getResourceName()
    {
        $class = get_class($this);

        if ($this->_app->getNamespace()) {
            $class = String::replace($class, $this->_app->getNamespace(), '');

            if (String::startsWith($class, '\\')) {
                $class = String::sub($class, 1);
            }

            if (String::startsWith($class, 'forms\\')) {
                $class = String::sub($class, 6);
            }
        }

        return String::replace($class, '\\', '/');
    }

    protected function applyConfig()
    {
        if ($this->_config->has('form.style')) {
            try {
                $this->style = $this->_config->get('form.style');
            } catch (Exception $e) {
                $this->style = 'DECORATED';
            }
        }

        // only for non primary forms.
        if ($this->_app->getMainForm() && $this->_config->has('form.modality')) {
            try {
                $this->modality = $this->_config->get('form.modality');
            } catch (Exception $e) {
                if ($this->_config->get('form.modality')) {
                    $this->modality = 'APPLICATION_MODAL';
                } else {
                    $this->modality = 'NONE';
                }
            }
        }

        foreach ([
                     'title', 'resizable', 'alwaysOnTop',
                     'minHeight', 'minWidth', 'maxHeight', 'maxWidth'
                 ] as $key) {
            if ($this->_config->has("form.$key")) {
                $this->{$key} = $this->_config->get("form.$key");
            }
        }

        $modules = $this->_config->getArray('modules', []);

        foreach ($modules as $type) {
            /** @var AbstractModule $module */
            if (!Str::contains($type, '\\') && $this->_app->getNamespace()) {
                $type = $this->_app->getNamespace() . "\\modules\\$type";
            }

            $module = new $type();
            $this->_modules[$module->id] = $module;
        }

        foreach ($this->_modules as $module) {
            UXApplication::runLater(function () use ($module) {
                $module->apply($this);
            });
        }
    }

    protected function loadConfig($path = null, $applyConfig = true)
    {
        if ($path === null) {
            $path = static::DEFAULT_PATH . $this->getResourceName() . '.conf';
        }

        try {
            $this->_config = new Configuration($path);
        } catch (IOException $e) {
            $this->_config = new Configuration();
        }

        if ($applyConfig) $this->applyConfig();
    }

    protected function loadDesign()
    {
        $loader = new UXLoader();

        $path = static::DEFAULT_PATH . $this->getResourceName() . '.fxml';

        Stream::tryAccess($path, function (Stream $stream) use ($loader) {
            try {
                $this->layout = $loader->load($stream);
            } catch (IOException $e) {
                throw new IOException("Unable to load {$stream->getPath()}, {$e->getMessage()}");
            }

            if ($this->layout) {
                DataUtils::scan($this->layout, function (UXData $data, UXNode $node = null) {
                    if ($node) {
                        $class = get_class($node) . 'Wrapper';

                        if (class_exists($class)) {
                            $wrapper = new $class($node);
                        } else {
                            $wrapper = new UXNodeWrapper($node);
                        }

                        $wrapper->applyData($data);
                        $data->free();
                    }
                });
            }
        });
    }

    /**
     * @param object $handler
     *
     * @throws Exception
     * @throws IllegalStateException
     */
    public function loadBindings($handler)
    {
        $class = new ReflectionClass($handler);
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

        $events = [];

        foreach ($methods as $method) {
            $comment = $method->getDocComment();

            $scanner = new Scanner($comment);

            while ($scanner->hasNextLine()) {
                $line = String::trim($scanner->nextLine());

                if (String::startsWith($line, '@event ')) {
                    $event = String::trim(String::sub($line, 7));

                    if (isset($events[$event])) {
                        throw new IllegalStateException(
                            "Unable to bind '$event' for {$method->getName()}(), this event already bound for {$events[$event]}()"
                        );
                    }

                    $methodName = $events[$event] = $method->getName();

                    $this->bind($event, [$handler, $methodName]);
                }
            }
        }
    }

    public function bind($event, callable $handler, $group = 'general')
    {
        $parts = Str::split($event, '.');

        $eventName = Items::pop($parts);

        if ($parts) {
            $id = Str::join($parts, '.');
            $node = $this->{$id};
        } else {
            $node = $this;
        }

        if (!$node) {
            throw new Exception("Unable to bind '$event'");
        }

        $node->on($eventName, $handler, $group);
    }
}