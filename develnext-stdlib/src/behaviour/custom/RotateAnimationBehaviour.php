<?php
namespace behaviour\custom;

use action\Animation;
use php\gui\framework\behaviour\custom\AnimationBehaviour;
use php\gui\framework\ScriptEvent;
use php\gui\UXNode;
use script\TimerScript;

class RotateAnimationBehaviour extends AnimationBehaviour
{
    /**
     * @var bool
     */
    public $negative = false;

    /**
     * @param mixed $target
     */
    protected function applyImpl($target)
    {
        if (!($target instanceof UXNode)) {
            return;
        }

        $timer = new TimerScript(25, true, function (ScriptEvent $e) use ($target) {
            if ($this->enabled) {
                $percent = ($e->sender->interval * 100 / $this->duration) / 100;

                $step = 360 * $percent;
                $target->rotate += $this->negative ? -$step : $step;
            }
        });

        $timer->start();
    }
}