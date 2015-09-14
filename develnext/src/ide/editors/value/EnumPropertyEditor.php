<?php
namespace ide\editors\value;
use php\gui\UXChoiceBox;
use php\lib\Items;
use php\gui\layout\UXHBox;
use php\lib\Str;
use php\lib\String;
use php\xml\DomElement;

/**
 * Class EnumPropertyEditor
 * @package ide\editors\value
 */
class EnumPropertyEditor extends ElementPropertyEditor
{
    /**
     * @var UXChoiceBox
     */
    protected $choiceBox;

    /**
     * @var array
     */
    protected $variants;

    /**
     * @var
     */
    protected $variantKeys;

    /**
     * @param $variants
     */
    public function __construct(array $variants = [])
    {
        $this->variants = $variants;
        $this->variantKeys = Items::keys($variants);

        parent::__construct();
    }


    public function makeUi()
    {
        $this->choiceBox = new UXChoiceBox();
        $this->choiceBox->maxWidth = 300;
        $this->choiceBox->items->addAll($this->variants);

        $this->choiceBox->style = "-fx-background-insets: 0; -fx-background-radius: 0; -fx-background-color: -fx-control-inner-background;";

        $this->choiceBox->on('action', function () {
            $this->applyValue($this->choiceBox->selectedIndex, false);
        });

        return new UXHBox([$this->choiceBox]);
    }

    public function getNormalizedValue($value)
    {
        if (Str::isNumber($value)) {
            if ($key = $this->variantKeys[$value]) {
                return $key;
            }
        }

        if (!$this->variants[$value]) {
            return Items::firstKey($this->variants);
        }

        return $value;
    }


    /**
     * @param $value
     */
    public function updateUi($value)
    {
        parent::updateUi($value);

        $i = 0;
        $this->choiceBox->selectedIndex = -1;

        foreach ($this->variants as $code => $name) {
            if ($value == $code) {
                $this->choiceBox->selectedIndex = $i;
                break;
            }

            $i++;
        }
    }

    public function getCode()
    {
        return 'enum';
    }

    /**
     * @param DomElement $element
     *
     * @return ElementPropertyEditor
     */
    public function unserialize(DomElement $element)
    {
        $variants = [];

        /** @var DomElement $el */
        foreach ($element->findAll('variants/variant') as $el) {
            $variants[$el->getAttribute('value')] = $el->getTextContent();
        }

        $editor = new static($variants);
        return $editor;
    }
}