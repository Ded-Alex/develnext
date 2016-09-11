<?php
namespace php\gui\event;

/**
 * Class UXKeyEvent
 * @package php\gui\event
 */
class UXKeyEvent extends UXEvent
{
    /**
     * @var string
     */
    public $character;

    /**
     * @var string
     */
    public $text;

    /**
     * @var string
     */
    public $codeName;

    /**
     * @var bool
     */
    public $altDown;

    /**
     * @var bool
     */
    public $controlDown;

    /**
     * @var bool
     */
    public $shiftDown;

    /**
     * @var bool
     */
    public $metaDown;

    /**
     * @var bool
     */
    public $shortcutDown;

    /**
     * @param UXKeyEvent $parent
     * @param $sender
     */
    public function __construct(UXKeyEvent $parent, $sender)
    {
    }

    /**
     * @param string $accelerator e.g. Control + R
     * @return bool
     */
    public function matches($accelerator)
    {
    }
}