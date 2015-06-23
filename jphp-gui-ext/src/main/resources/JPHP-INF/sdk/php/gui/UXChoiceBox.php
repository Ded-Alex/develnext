<?php
namespace php\gui;

/**
 * Class UXChoiceBox
 * @package php\gui
 */
class UXChoiceBox extends UXControl
{
    /**
     * @var UXList
     */
    public $items;

    /**
     * @var mixed
     */
    public $value = null;

    /**
     * @var int
     */
    public $selectedIndex = -1;
}