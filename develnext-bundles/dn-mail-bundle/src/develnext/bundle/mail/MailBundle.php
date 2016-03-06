<?php
namespace develnext\bundle\mail;

use develnext\bundle\mail\components\MailScriptComponent;
use ide\bundle\AbstractBundle;
use ide\formats\ScriptModuleFormat;
use ide\Ide;
use ide\project\behaviours\GuiFrameworkProjectBehaviour;
use ide\project\Project;
use php\lib\fs;

class MailBundle extends AbstractBundle
{
    function getName()
    {
        return "Отправитель писем";
    }

    function getDescription()
    {
        return "Пакет для отправки электронным писем (email) через smtp сервер";
    }

    public function isAvailable(Project $project)
    {
        return $project->hasBehaviour(GuiFrameworkProjectBehaviour::class);
    }

    public function getDependencies()
    {
        return [
            JPHPMailBundle::class
        ];
    }

    public function onPreCompile(Project $project, $env, callable $log = null)
    {
        $file = $project->getSrcFile('script/MailScript.php', true);
        fs::ensureParent($file);

        fs::copy('res://script/MailScript.php', $file);
    }

    public function onAdd(Project $project)
    {
        $format = Ide::get()->getRegisteredFormat(ScriptModuleFormat::class);

        if ($format) {
            $format->register(new MailScriptComponent());
        }
    }

    public function onRemove(Project $project)
    {
        $format = Ide::get()->getRegisteredFormat(ScriptModuleFormat::class);

        if ($format) {
            $format->unregister(new MailScriptComponent());
        }
    }
}