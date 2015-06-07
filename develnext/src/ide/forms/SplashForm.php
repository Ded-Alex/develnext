<?php
namespace ide\forms;

use php\gui\framework\AbstractForm;
use php\gui\UXApplication;
use php\gui\UXImageView;
use php\gui\UXLabel;
use php\lang\Thread;
use php\lang\ThreadPool;
use php\gui\framework\Timer;

/**
 * @property UXLabel $version
 */
class SplashForm extends AbstractForm
{
    protected function init()
    {
        $this->_origin->centerOnScreen();

        $this->version->text = $this->_app->getVersion();

        Timer::run(2000, function() {
            if ($this->_app->getMainForm()->isShowing()) {
                $this->hide();
            }
        });
    }

    /**
     * @event click
     */
    public function hide()
    {
        parent::hide();
    }
}