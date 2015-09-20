<?php
namespace ide\action;

use ide\forms\ActionArgumentsDialog;
use php\jsoup\Document;
use php\lib\Str;
use php\xml\DomDocument;
use php\xml\DomElement;

abstract class AbstractSimpleActionType extends AbstractActionType
{
    const GROUP_OTHER = 'Другок';
    const GROUP_APP = 'Система';
    const GROUP_UI = 'Интерфейс';
    const GROUP_CONDITIONS = 'Условия';
    const GROUP_SCRIPT = 'Скрипт';

    const SUB_GROUP_WINDOW = 'Форма';
    const SUB_GROUP_COMPONENT = 'Объект';
    const SUB_GROUP_COMMON = 'Главное';
    const SUB_GROUP_DECOR = 'Декорация';
    const SUB_GROUP_ANIMATION = 'Анимация';

    /**
     * @return array
     */
    function attributes()
    {
        return [];
    }

    function attributeLabels()
    {
        return [];
    }

    function  attributeSettings()
    {
        return [];
    }

    function getSubGroup()
    {
        return self::SUB_GROUP_COMMON;
    }

    function fetchFieldValue(Action $action, $field, $value)
    {
        $type = $action->{"$field-type"};

        switch ($type) {
            case 'variable':
                if (!$value) {
                    $value = 'any';
                }

                if ($value[0] != '$') {
                    $value = '$' . $value;
                }

                return $value;

            case 'object':
                if ($value == '~sender') {
                    $result = "\$event->sender";
                } else if ($value == '~senderForm') {
                    $result = "\$this->getContextForm()";
                } else {
                    $result = $value ? "\$this->$value" : "\$this";
                }

                $t = $this->attributes()[$field];

                if ($t == "string" || $t == "integer") {
                    $result = "uiText($result)";
                }

                return $result;

            case 'form':
                if ($value == '~sender') {
                    return "\$this->getContextFormName()";
                }

                return "'$value'";

            case 'string':
                return "'$value'";

            case 'magicString':
                return '"' . $value . '"';

            case 'integer':
                return ((int) $value) . '';

            case 'float':
                return ((double) $value) . '';

            case 'expr':
                return $value;

            default:
                $type = $this->attributes()[$field];

                switch ($type) {
                    case 'string':
                        return "'$value'";
                    case 'integer':
                        return ((int) $value) . '';
                    case 'float':
                        return ((float) $value) . '';
                    case 'boolean':
                        return $value ? 'true' : 'false';
                    case 'expr':
                        return $value;
                }
        }

        return parent::fetchFieldValue($action, $field, $value);
    }

    /**
     * @param Action $action
     * @param DomElement $element
     * @param DomDocument $document
     */
    function serialize(Action $action, DomElement $element, DomDocument $document)
    {
        foreach ($this->attributes() as $name => $info) {
            $element->setAttribute("{$name}-type", $action->{"$name-type"});

            $element->setAttribute($name, $action->{$name});
        }
    }

    /**
     * @param Action $action
     * @param DomElement $element
     */
    function unserialize(Action $action, DomElement $element)
    {
        foreach ($this->attributes() as $name => $info) {
            $action->{$name} = $element->getAttribute($name);
            $action->{"$name-type"} = $element->getAttribute("$name-type");
        }
    }

    /**
     * @param Action $action
     * @param $userData
     * @param bool $asNew
     * @return bool
     */
    function showDialog(Action $action, $userData = null, $asNew = false)
    {
        if (!$this->attributes()) {
            return true;
        }

        $dialog = new ActionArgumentsDialog();
        $dialog->userData = $userData;
        $dialog->setAction($action, $asNew);

        if ($dialog->showDialog()) {
            $result = $dialog->getResult();

            foreach ($result as $name => $value) {
                $action->{$name} = $value;
            }

            return true;
        }

        return false;
    }
}