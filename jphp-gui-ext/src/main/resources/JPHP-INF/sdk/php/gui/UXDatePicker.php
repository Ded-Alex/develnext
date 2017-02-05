<?php
namespace php\gui;

/**
 * Class UXDatePicker
 * @package php\gui
 * @packages gui, javafx
 */
class UXDatePicker extends UXComboBoxBase
{


    /**
     * @readonly
     * @var UXTextField
     */
    public $editor;

    /**
     * @var string
     */
    public $format = 'yyyy-MM-dd';

    /**
     * @var bool
     */
    public $showWeekNumbers;
}