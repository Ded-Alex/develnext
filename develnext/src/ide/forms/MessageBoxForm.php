<?php
namespace ide\forms;

use ide\forms\mixins\DialogFormMixin;
use ide\Ide;
use php\gui\framework\AbstractForm;
use php\gui\layout\UXHBox;
use php\gui\UXButton;
use php\gui\UXControl;
use php\gui\UXForm;
use php\gui\UXImageView;
use php\gui\UXLabel;
use php\gui\UXNode;

/**
 * @property UXHBox $buttonBox
 * @property UXLabel $messageLabel
 * @property UXImageView $icon
 *
 * Class MessageBoxForm
 * @package ide\forms
 */
class MessageBoxForm extends AbstractForm
{
    use DialogFormMixin;

    /** @var string */
    protected $text;

    /** @var array */
    protected $buttons = [];

    /**
     * @param string $text
     * @param array $buttons
     */
    public function __construct($text, array $buttons)
    {
        parent::__construct();

        $this->text = $text;
        $this->buttons = $buttons;
    }

    /**
     * @event show
     */
    public function doOpen()
    {
        $this->icon->image = Ide::get()->getImage('icons/question32.png')->image;

        $this->iconified = false;
        $this->messageLabel->text = $this->text;

        $i = 0;
        foreach ($this->buttons as $value => $button)
        {
            if ($button instanceof UXNode) {
                $this->buttonBox->add($button);
                continue;
            }

            $ui = new UXButton($button);
            $ui->minWidth = 100;
            $ui->maxHeight = 10000;

            if ($i++ == 0) {
                $ui->style = '-fx-font-weight: bold';
            }

            $ui->on('action', function() use ($value) {
                $this->setResult($value);
                $this->hide();
            });

            $this->buttonBox->add($ui);
        }
    }
}