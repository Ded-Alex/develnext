<?php
namespace ide\scripts;
use ide\formats\form\AbstractFormElement;
use ide\formats\form\FormElementConfig;
use ide\misc\GradleBuildConfig;
use php\gui\designer\UXDesignProperties;
use php\gui\UXNode;

/**
 * Class AbstractScriptComponent
 * @package ide\scripts
 */
abstract class AbstractScriptComponent extends AbstractFormElement
{
    /**
     * @return string
     */
    abstract public function getType();

    /**
     * @param GradleBuildConfig $gradleBuild
     *
     * @return array
     */
    public function adaptForGradleBuild(GradleBuildConfig $gradleBuild)
    {
        $gradleBuild->setDependency('develnext-stdlib');
    }

    public function applyProperties(UXDesignProperties $properties)
    {
        if ($this->config) {
            foreach ($this->config->getPropertyGroups() as $code => $group) {
                $properties->addGroup($code, $group['title']);
            }

            foreach ($this->config->getProperties() as $code => $property) {
                $editorFactory = $property['editorFactory'];
                $editor = $editorFactory();

                if ($editor) {
                    $properties->addProperty($property['group'], $property['code'], $property['name'], $editor);
                }
            }
        }
    }

    /**
     * @return null|string|UXImage
     */
    public function getIcon()
    {
        return null;
    }

    abstract public function getDescription();

    public function getPlaceholder(ScriptComponentContainer $container)
    {
        return $this->getDescription();
    }

    /**
     * @return UXNode
     */
    public function createElement()
    {
        // TODO: Implement createElement() method.
    }
}