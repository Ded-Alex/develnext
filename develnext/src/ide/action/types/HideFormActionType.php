<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use php\lib\Str;

class HideFormActionType extends AbstractSimpleActionType
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
            'form' => 'Форма'
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
        return 'hideForm';
    }

    function getTitle(Action $action = null)
    {
        return 'Закрыть форму';
    }

    function getDescription(Action $action = null)
    {
        if ($action == null) {
            return "Закрыть форму";
        }

        return Str::format("Закрыть форму %s", $action->get('form'));
    }

    function getIcon(Action $action = null)
    {
        return 'icons/hideForm16.png';
    }

    /**
     * @param Action $action
     * @return string
     */
    function convertToCode(Action $action)
    {
        $form = $action->get('form');

        return "app()->hideForm({$form})";
    }
}