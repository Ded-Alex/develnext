<?php
namespace ide\formats\form\tags;

use ide\formats\form\AbstractFormDumper;
use ide\formats\form\AbstractFormElementTag;
use php\gui\layout\UXAnchorPane;
use php\gui\UXNode;
use php\xml\DomDocument;
use php\xml\DomElement;

class NodeFormElementTag extends AbstractFormElementTag
{
    public function getTagName()
    {
        return 'Node';
    }

    public function getElementClass()
    {
        return UXNode::class;
    }

    public function isAbstract()
    {
        return true;
    }

    public function writeAttributes($node, DomElement $element)
    {
        /** @var UXNode $node */
        $element->setAttribute('id', $node->id);

        if ($node->style) {
            $element->setAttribute('style', $node->style);
        }

        $element->setAttribute('layoutX', $node->x);
        $element->setAttribute('layoutY', $node->y);

        $element->setAttribute('prefWidth', $node->size[0]);
        $element->setAttribute('prefHeight', $node->size[1]);

        if ($node->opacity < 1) {
            $element->setAttribute('opacity', $node->opacity);
        }

        if ($node->rotate > 0) {
            $element->setAttribute('rotate', $node->rotate);
        }
    }

    public function writeContent($node, DomElement $element, DomDocument $document, AbstractFormDumper $dumper)
    {
    }
}