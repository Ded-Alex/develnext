<?php
namespace php\gui;

use php\gui\event\UXEvent;
use php\gui\layout\UXPane;
use php\lang\IllegalStateException;

/**
 * Class UXWindow
 * @package php\gui
 *
 * @property double $x
 * @property double $y
 * @property double $width
 * @property double $height
 * @property double $opacity
 * @property bool $focused
 */
abstract class UXWindow
{
    /**
     * @var UXScene
     */
    public $scene;

    /**
     * [width, height]
     * @var double[]
     */
    public $size;

    /**
     * @var UXPane
     */
    public $layout;

    /**
     * @readonly
     * @var UXList
     */
    public $children;

    /**
     * @var bool
     */
    public $visible;

    /**
     * @var string
     */
    public $cursor;

    public function requestFocus()
    {
    }

    /**
     * ...
     */
    public function show()
    {
    }

    /**
     * ...
     */
    public function hide()
    {
    }

    /**
     * ...
     */
    public function centerOnScreen()
    {
    }

    /**
     * ...
     */
    public function sizeToScene()
    {
    }

    /**
     * @param string $event
     * @param callable $handler
     * @param string $group
     */
    public function on($event, callable $handler, $group = 'general')
    {
    }

    /**
     * @param string $event
     * @param string $group (optional)
     */
    public function off($event, $group)
    {
    }

    /**
     * @param string $event
     * @param UXEvent $e (optional)
     */
    public function trigger($event, UXEvent $e)
    {
    }

    /**
     * @param string $property
     * @param callable $listener (UXWindow $self, $property, $oldValue, $newValue)
     */
    public function watch($property, callable $listener)
    {
    }

    /**
     * @param UXNode $node
     *
     * @throws IllegalStateException
     */
    public function add(UXNode $node)
    {
    }

    /**
     * @param UXNode $node
     *
     * @return bool
     * @throws IllegalStateException
     */
    public function remove(UXNode $node)
    {
    }

    /**
     * @param string $path
     */
    public function addStylesheet($path)
    {
    }

    /**
     * @param string $id
     *
     * @return UXNode|null
     */
    public function __get($id)
    {
    }

    /**
     * @param string $id
     */
    public function __isset($id)
    {
    }
}