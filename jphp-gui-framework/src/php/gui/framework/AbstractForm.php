<?php
namespace php\gui\framework;

use action\Animation;
use Exception;
use php\gui\AbstractFormWrapper;
use php\gui\event\UXEvent;
use php\gui\event\UXKeyEvent;
use php\gui\framework\behaviour\custom\AbstractBehaviour;
use php\gui\framework\behaviour\custom\BehaviourLoader;
use php\gui\framework\behaviour\custom\BehaviourManager;
use php\gui\framework\behaviour\custom\FormBehaviourManager;
use php\gui\framework\event\AbstractEventAdapter;
use php\gui\layout\UXAnchorPane;
use php\gui\paint\UXColor;
use php\gui\UXApplication;
use php\gui\UXData;
use php\gui\UXDialog;
use php\gui\UXForm;
use php\gui\UXImage;
use php\gui\UXLabel;
use php\gui\UXLoader;
use php\gui\UXNode;
use php\gui\UXNodeWrapper;
use php\gui\UXProgressIndicator;
use php\gui\UXTooltip;
use php\gui\UXWindow;
use php\io\File;
use php\io\IOException;
use php\io\Stream;
use php\lang\IllegalArgumentException;
use php\lang\IllegalStateException;
use php\lang\Thread;
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
     * @var BehaviourManager
     */
    protected $behaviourManager;

    /**
     * @param UXForm $origin
     * @throws Exception
     */
    public function __construct(UXForm $origin = null)
    {
        parent::__construct($origin);

        $name = Str::replace(get_class($this), '\\', '/');

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

        $this->behaviourManager = $behaviourManager = new FormBehaviourManager($this);
        BehaviourLoader::load("res://$name.behaviour", $behaviourManager);
    }

    public function behaviour($target, $class)
    {
        return $this->behaviourManager->getBehaviour($target, $class);
    }

    /**
     * @param $name
     * @return AbstractForm
     */
    public function form($name)
    {
        return $this->_app->getForm($name);
    }

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * @return string
     */
    public function getName()
    {
        $namespace = app()->getNamespace();

        if ($namespace) {
            return Str::replace(get_class($this), "$namespace\\forms\\", "");
        } else {
            $name = Str::split(get_class($this), "\\");

            return $name[sizeof($name) - 1];
        }
    }

    public function show()
    {
        parent::show();

        if ($this->_config && $this->_config->get('form.maximized')) {
            $this->maximized = true;
            $this->maximize();
        }
    }

    public function loadForm($form, $saveSize = false, $savePosition = false)
    {
        $form = $this->_app->getForm($form);

        if ($form) {
            if ($saveSize) {
                UXApplication::runLater(function () use ($form) {
                    $form->size = $this->size;
                    $form->centerOnScreen();
                });
            }

            if ($savePosition) {
                UXApplication::runLater(function () use ($form) {
                    $form->x = $this->x;
                    $form->y = $this->y;
                });
            }

            $form->show();
            $this->free();
        }
    }

    public function free()
    {
        $this->hide();
    }

    public function getContextForm()
    {
        return $this;
    }

    public function getContextFormName()
    {
        return $this->getName();
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
            $class = Str::replace($class, $this->_app->getNamespace(), '');

            if (Str::startsWith($class, '\\')) {
                $class = Str::sub($class, 1);
            }

            if (Str::startsWith($class, 'forms\\')) {
                $class = Str::sub($class, 6);
            }
        }

        return Str::replace($class, '\\', '/');
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
                $value = $this->_config->get('form.modality');

                if ($value == '1') {
                    $value = 'APPLICATION_MODAL';
                } elseif ($value == '0') {
                    $value = 'NONE';
                }

                $this->modality = $value;
            } catch (Exception $e) {
                ;
            }
        }

        if ($this->_config->has('form.icon')) {
            try {
                $this->icons->add(new UXImage("res://" . $this->_config->get('form.icon')));
            } catch (Exception $e) {
                ;
            }
        } else {
            if ($icon = $this->_app->getConfig()->get('app.icon')) {
                $this->icons->add(new UXImage("res://" . $icon));
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

        if ($this->_config->get('form.backgroundColor')) {
            try {
                $this->layout->backgroundColor = UXColor::of($this->_config->get('form.backgroundColor'));
            } catch (Exception $e) {
                ;
            }
        }

        if ($this->style == 'TRANSPARENT') {
            $this->transparent = true;
            $this->style = 'TRANSPARENT';
            $this->layout->backgroundColor = null;
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
                        $this->getNodeWrapper($node)->applyData($data);
                        $data->free();
                    }
                });
            }
        });
    }

    /**
     * @param UXWindow|UXNode $node
     * @return UXNodeWrapper
     */
    public function getNodeWrapper($node)
    {
        $wrapper = $node->data('~wrapper');

        if ($wrapper) {
            return $wrapper;
        }

        if ($node instanceof AbstractForm) {
            $wrapper = new AbstractFormWrapper($this);
        } else {
            $class = get_class($node) . 'Wrapper';

            if (class_exists($class)) {
                $wrapper = new $class($node);
            } else {
                $wrapper = new UXNodeWrapper($node);
            }
        }

        $node->data('~wrapper', $wrapper);

        return $wrapper;
    }

    public function toast($message, $timeout = 0)
    {
        UXApplication::runLater(function () use ($message, $timeout) {
            $tooltip = new UXTooltip();
            $tooltip->text = $message;

            if ($timeout <= 0) {
                $length = Str::length($message);
                $timeout = $length * 100;

                if ($timeout < 1500) {
                    $timeout = 1500;
                }
            }

            $width = $tooltip->font->calculateTextWidth($message);
            $height = $tooltip->font->lineHeight;
            $tooltip->layout->style = '-fx-cursor: hand;';

            $tooltip->on('click', function ($e) {
                $e->sender->hide();
            });

            $tooltip->opacity = 0;
            $tooltip->show($this, $this->x + $this->width / 2 - $width / 2, $this->y + $this->height / 2 - $height / 2);

            Animation::fadeIn($tooltip, 300);

            (new Thread(function () use ($timeout, $tooltip) {
                Thread::sleep($timeout);

                try {
                    UXApplication::runLater(function () use ($tooltip) {
                        Animation::fadeOut($tooltip, 300, function () use ($tooltip) {
                            $tooltip->hide();
                        });
                    });
                } catch (IllegalStateException $e) {
                    // ..
                }
            }))->start();
        });
    }

    public function showPreloader($text = '', $timeout = 0)
    {
        $this->hidePreloader();

        $pane = $this->layout;

        $preloader = new UXAnchorPane();
        UXAnchorPane::setAnchor($preloader, 0);
        $preloader->size = $pane->size;

        $preloader->position = [0, 0];
        $preloader->opacity = 0.6;

        $indicator = new UXProgressIndicator();
        $indicator->progress = -1;
        $indicator->size = [48, 48];

        $label = null;

        if ($text) {
            $label = new UXLabel($text);
            $label->text = $text;
            $preloader->add($label);
        }

        $preloader->watch('width', function () use ($pane, $indicator, $label, $text) {
            $indicator->x = $pane->width / 2 - $indicator->width / 2;

            if ($label) {
                $label->x = $pane->width / 2 - $label->font->calculateTextWidth($text) / 2;
            }
        });

        $preloader->watch('height', function () use ($pane, $indicator, $label) {
            $indicator->y = $pane->height / 2 - $indicator->height / 2;

            if ($label) {
                $label->y = $indicator->y + $indicator->height + 5;
            }
        });

        $preloader->add($indicator);

        $preloader->id = 'x-preloader';
        $preloader->style = '-fx-background-color: white';

        $pane->add($preloader);

        $preloader->toFront();

        if ($timeout) {
            (new Thread(function () use ($timeout) {
                Thread::sleep($timeout);

                UXApplication::runLater(function () {
                    $this->hidePreloader();
                });
            }))->start();
        }
    }

    public function hidePreloader()
    {
        $preloader = $this->layout->lookup('#x-preloader');

        if ($preloader) {
            $preloader->free();
        }
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
                $line = Str::trim($scanner->nextLine());

                if (Str::startsWith($line, '@event ')) {
                    $event = Str::trim(Str::sub($line, 7));

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

        $eventName = Str::split($eventName, '-', 2);


        if ($eventName[1]) {
            $class = "php\\gui\\framework\\event\\" . Str::upperFirst(Str::lower($eventName[0])) . "EventAdapter";

            static $adapters;

            if ($adapters[$class]) {
                $adapter = $adapters[$class];
            } elseif (class_exists($class)) {
                /** @var AbstractEventAdapter $adapter */
                $adapter = new $class();
                $adapters[$class] = $adapter;
            } else {
                $adapter = null;
            }

            if ($adapter == null) {
                throw new Exception("Unable to bind '$event'");
            }

            $handler = $adapter->adapt($node, $handler, $eventName[1]);

            if (!$handler) {
                throw new Exception("Unable to bind '$event'");
            }

            $group = $event;
        }

        $wrapper = $this->getNodeWrapper($node);
        $wrapper->bind($eventName[0], $handler, $group);
    }

    public function __get($name)
    {
        foreach ($this->_modules as $module) {
            if ($module->disabled) {
                continue;
            }

            if ($script = $module->getScript($name)) {
                return $script;
            }
        }

        return parent::__get($name);
    }

    public function __isset($name) {
        foreach ($this->_modules as $module) {
            if ($module->disabled) {
                continue;
            }

            if ($script = $module->getScript($name)) {
                return true;
            }
        }

        return parent::__isset($name);
    }
}