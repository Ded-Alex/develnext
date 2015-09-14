<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use php\lib\Str;

class ShowNewFormActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'form' => 'form',
        ];
    }

    function attributeLabels()
    {
        return [
            'form' => 'Название формы'
        ];
    }

    function getSubGroup()
    {
        return self::SUB_GROUP_WINDOW;
    }

    function getGroup()
    {
        return self::GROUP_UI;
    }

    function getTagName()
    {
        return 'showNewForm';
    }

    function getTitle(Action $action = null)
    {
        return 'Открыть новую форму';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "Открыть новую форму";
        }

        return Str::format("Открыть новую форму %s", $action->get('form'));
    }

    function getIcon(Action $action = null)
    {
        return 'icons/showNewForm16.png';
    }

    /**
     * @param Action $action
     * @return string
     */
    function convertToCode(Action $action)
    {
        $form = $action->get('form');

        return "app()->showNewForm({$form})";
    }
}