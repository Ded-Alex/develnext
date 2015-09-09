<?php
namespace php\gui;

/**
 * Class UXComboBoxBase
 * @package php\gui
 *
 * @method show()
 * @method hide()
 * @method arm()
 * @method disarm()
 */
class UXComboBoxBase extends UXControl
{
    /**
     * @var bool
     */
    public $armed;

    /**
     * @var bool
     */
    public $editable;

    /**
     * @var string
     */
    public $promptText;

    /**
     * @readonly
     * @var bool
     */
    public $showing;

    /**
     * @var mixed
     */
    public $value;

    /**
     * @var string
     */
    public $text;
}