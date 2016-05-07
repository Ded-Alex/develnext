<?php
namespace php\gui;

use php\gui\animation\UXAnimationTimer;
use php\gui\event\UXEvent;
use php\gui\event\UXKeyboardManager;
use php\gui\event\UXKeyEvent;
use php\gui\framework\AbstractForm;
use php\gui\framework\View;
use php\lib\str;
use script\TimerScript;

class UXNodeWrapper
{
    /**
     * @var UXNode
     */
    protected $node;

    /**
     * UXNodeWrapper constructor.
     *
     * @param $node
     */
    public function __construct(UXNode $node)
    {
        $this->node = $node;
    }

    /**
     * @param UXData $data
     */
    public function applyData(UXData $data)
    {
        if ($data->has('classesString')) {
            $this->node->classesString .= $data->get('classesString');
        }

        if ($data->has('enabled')) {
            $this->node->enabled = $data->get('enabled');
        }

        if ($data->has('visible')) {
            $this->node->visible = $data->get('visible');
        }

        if ($data->has('tooltipText') && $this->node instanceof UXControl) {
            $this->node->tooltipText = $data->get('tooltipText');
        }

        if ($data->has('cursor') && $data->get('cursor') !== 'DEFAULT') {
            $this->node->cursor = $data->get('cursor');
        }

        $this->node->data('--start-position', $this->node->position);
    }

    protected function bindGlobalKey($event, callable $_handler, $group)
    {
        $form = $this->node->form;

        if ($form) {
            $manager = $form->data(UXKeyboardManager::class);

            if (!$manager) {
                $manager = new UXKeyboardManager($form);
                $form->data(UXKeyboardManager::class, $manager);
            }

            list($kind, $param) = str::split($event, '-', 2);

            $group = $this->node->id . "-$group-$param";

            $param = $param ? $param : null;

            $handler = function (UXKeyEvent $e) use ($_handler) {
                if ($this->node->isFree() || !$this->node->enabled) {
                    return;
                }

                $e = new UXKeyEvent($e, $this->node);

                uiLater(function () use ($_handler, $e) {
                    $_handler($e);
                });
            };

            switch ($kind) {
                case 'globalKeyPress':
                    $manager->onPress($param, $handler, $group);
                    break;

                case 'globalKeyDown':
                    $manager->onDown($param, $handler, $group);
                    break;

                case 'globalKeyUp':
                    $manager->onUp($param, $handler, $group);
                    break;

                default:
                    throw new \Exception("Unable to bind '$kind' event with param = '$param'");
            }
        } else {
            TimerScript::executeWhile(function () {
                return $this->node->form;
            }, function () use ($event, $_handler, $group) {
                $this->bindGlobalKey($event, $_handler, $group);
            });
        }
    }

    /**
     * @param $event
     * @param callable $handler
     * @param $group
     */
    public function bind($event, callable $handler, $group)
    {
        switch ($event) {
            case 'construct':
                uiLater(function () use ($handler) {
                    $handler(UXEvent::makeMock($this->node));
                });
                return;

            case 'create':
                if ($this->node->data('-factory-id')) { // if is clone!
                    uiLater(function () use ($handler) {
                        $handler(UXEvent::makeMock($this->node));
                    });
                }
                return;

            case 'destroy':
                $this->node->observer('parent')->addListener(function ($old, $new) use ($handler) {
                    if (!$new) {
                        if (!$this->node->isFree()) {
                            $handler(UXEvent::makeMock($this->node));
                        }
                    }
                });

                return;

            case 'step':
                $stepTimer = new UXAnimationTimer(function () use ($handler) {
                    if (!$this->node->isFree() && $this->node->enabled) {
                        $handler(UXEvent::makeMock($this->node));
                    }
                });

                if (!$this->node->isFree()) {
                    $stepTimer->start();
                }

                $this->node->observer('parent')->addListener(function ($old, $new) use ($stepTimer) {
                    if ($new) {
                        $stepTimer->start();
                    } else {
                        $stepTimer->stop();
                    }
                });

                return;

            case 'outside-partly':
                $listener = function () use ($handler) {
                    if (!$this->node->enabled) {
                        return;
                    }

                    $handle = function () use ($handler) {
                        uiLater(function () use ($handler) {
                            $handler(UXEvent::makeMock($this->node));
                        });
                    };

                    if ($parent = $this->node->parent) {
                        $x = $this->node->x;
                        $y = $this->node->y;

                        $bounds = View::bounds($parent);

                        if ($x < $bounds['x']) {
                            $handle();
                        } elseif ($y < $bounds['y']) {
                            $handle();
                        } elseif ($x > $bounds['width'] - $this->node->width  + $bounds['x']) {
                            $handle();
                        } elseif ($y > $bounds['height'] - $this->node->height  + $bounds['y']) {
                            $handle();
                        }
                    }
                };

                $this->node->observer('layoutX')->addListener($listener);
                $this->node->observer('layoutY')->addListener($listener);
                return;

            case 'outside':
                $listener = function () use ($handler) {
                    if (!$this->node->enabled) {
                        return;
                    }

                    $handle = function () use ($handler) {
                        uiLater(function () use ($handler) {
                            $handler(UXEvent::makeMock($this->node));
                        });
                    };

                    if ($parent = $this->node->parent) {
                        $x = $this->node->x;
                        $y = $this->node->y;

                        $bounds = View::bounds($parent);

                        if ($x + $this->node->width < $bounds['x']) {
                            $handle();
                        } elseif ($y + $this->node->height < $bounds['y']) {
                            $handle();
                        } elseif ($x > $bounds['width'] + $bounds['x']) {
                            $handle();
                        } elseif ($y > $bounds['height'] + $bounds['y']) {
                            $handle();
                        }
                    }
                };

                $this->node->observer('layoutX')->addListener($listener);
                $this->node->observer('layoutY')->addListener($listener);
                return;
        }

        if (str::startsWith($event, 'globalKey')) {
            $this->bindGlobalKey($event, $handler, $group);
        } else {
            $this->node->on($event, $handler, $group);
        }
    }

    /**
     * @param AbstractForm|UXNode $node
     * @return AbstractFormWrapper|UXNodeWrapper
     */
    static function get($node)
    {
        $wrapper = $node->data('~wrapper');

        if ($wrapper) {
            return $wrapper;
        }

        if ($node instanceof AbstractForm) {
            $wrapper = new AbstractFormWrapper($node);
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
}