<?php
namespace php\gui;
use php\io\Stream;

/**
 * Class UXApplication
 * @package php\gui
 */
class UXApplication {
    /**
     * @param string|Stream $value css file
     */
    public static function setTheme($value) {}

    /**
     * @param callable $onStart (UXStage $stage)
     */
    public static function launch(callable $onStart) {}

    /**
     * @param callable $callback
     */
    public static function runLater(callable $callback) {}
}