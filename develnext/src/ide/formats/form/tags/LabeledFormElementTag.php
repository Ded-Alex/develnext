<?php
namespace ide\formats\form\tags;

use ide\formats\form\AbstractFormDumper;
use ide\formats\form\AbstractFormElementTag;
use php\gui\UXDialog;
use php\gui\UXLabeled;
use php\xml\DomDocument;
use php\xml\DomElement;

class LabeledFormElementTag extends AbstractFormElementTag
{
    public function getTagName()
    {
        return 'Labeled';
    }

    public function getElementClass()
    {
        return UXLabeled::class;
    }

    public function writeAttributes($node, DomElement $element)
    {
        /** @var UXLabeled $node */
        $element->setAttribute('text', $node->text);
        $element->setAttribute('alignment', $node->alignment);

        $textColor = $node->textColor;

        if ($textColor) {
            $element->setAttribute('textFill', $textColor->getWebValue());
        }
    }

    public function writeContent($node, DomElement $element, DomDocument $document, AbstractFormDumper $dumper)
    {
        /** @var UXLabeled $node */
        $font = $node->font;

        if ($font && ($font->family !== 'System' || $font->size != 12)) {
            $domFontProperty = $document->createElement('font');

            $domFont = $document->createElement('Font');
            $domFont->setAttribute('name', $font->name);
            $domFont->setAttribute('size', $font->size);

            $domFontProperty->appendChild($domFont);

            $element->appendChild($domFontProperty);
        }
    }
}