<?php
namespace ide\project\templates;

use ide\project\AbstractProjectTemplate;
use ide\project\behaviours\BundleProjectBehaviour;
use ide\project\behaviours\GuiFrameworkProjectBehaviour;
use ide\project\behaviours\PhpProjectBehaviour;
use ide\project\Project;

/**
 * Class DefaultGuiProjectTemplate
 * @package ide\project\templates
 */
class DefaultGuiProjectTemplate extends AbstractProjectTemplate
{
    public function getName()
    {
        return 'Десктопная программа';
    }

    public function getDescription()
    {
        return 'Программа с GUI интерфейсом для запуска на Linux/Windows/MacOS';
    }

    public function getIcon()
    {
        return 'icons/program16.png';
    }

    public function getIcon32()
    {
        return 'icons/programEx32.png';
    }

    public function recoveryProject(Project $project)
    {
        if (!$project->hasBehaviour(GuiFrameworkProjectBehaviour::class)) {
            $project->register(new GuiFrameworkProjectBehaviour());
        }

        if (!$project->hasBehaviour(PhpProjectBehaviour::class)) {
            $project->register(new PhpProjectBehaviour());
        }

        if (!$project->hasBehaviour(BundleProjectBehaviour::class)) {
            $project->register(new BundleProjectBehaviour());
        }
    }

    /**
     * @param Project $project
     *
     * @return Project
     */
    public function makeProject(Project $project)
    {
        $project->register(new GuiFrameworkProjectBehaviour());
        $project->register(new PhpProjectBehaviour());
        $project->register(new BundleProjectBehaviour());

        $project->setIgnoreRules([
            '*.log', '*.tmp'
        ]);

        return $project;
    }
}