<?php
namespace ide\action;
use ide\utils\FileUtils;
use ide\utils\PhpParser;
use php\format\ProcessorException;
use php\lib\Items;
use php\lib\Str;
use php\util\Flow;
use php\util\SharedStack;
use php\xml\DomDocument;
use php\xml\DomElement;
use php\xml\XmlProcessor;

/**
 * Class ActionContainer
 * @package ide\editors\action
 */
class ActionScript
{
    /**
     * @var DomDocument
     */
    protected $document;

    /**
     * @var ActionManager
     */
    protected $manager;

    /**
     * @var array
     */
    protected $localVariables;

    /**
     * ActionContainer constructor.
     * @param DomDocument $document
     * @param ActionManager $manager
     */
    public function __construct(DomDocument $document = null, ActionManager $manager = null)
    {
        $this->manager = $manager ?: ActionManager::get();
        $this->document = $document ?: (new XmlProcessor())->createDocument();
    }

    public function load($xmlFile)
    {
        try {
            $this->document = (new XmlProcessor())->parse(FileUtils::get($xmlFile));
        } catch (ProcessorException $e) {
            $this->document = (new XmlProcessor())->createDocument();
        }
    }

    public function save($xmlFile)
    {
        FileUtils::put($xmlFile, (new XmlProcessor())->format($this->document));
    }


    /**
     * @param Action[] $actions
     */
    public static function calculateLevels(array $actions)
    {
        $level = 0;

        /** @var Action $prevAction */
        $prevAction = null;

        $singleLevel = 0;

        foreach ($actions as $action) {
            $action->setLevel($level);

            if ($action->getType()->isCloseLevel()) {
                $level -= 1;
                //$singleLevel = 0;
            } elseif ($prevAction && $prevAction->getType()->isAppendSingleLevel()) {
                if ($action->getType()->isAppendSingleLevel()) {
                    //$singleLevel += 1;
                } else {
                    $level -= 1;
                }
            }

            if ($action->getType()->isAppendMultipleLevel() || $action->getType()->isAppendSingleLevel()) {
                $level += 1;

                if ($action->getType()->isAppendMultipleLevel()) {
                    $action->setLevel($level);
                }
            }

            $prevAction = $action;
        }
    }

    /**
     * @return array
     */
    public function getLocalVariables()
    {
        return $this->localVariables;
    }

    public function addLocalVariable($name)
    {
        if ($name[0] == '$') {
            $name = str::sub($name, 1);
        }

        $this->localVariables[$name] = $name;
    }

    public function compileActions($class, $method, array $actions, $comment = '', $endComment = '')
    {
        ActionScript::calculateLevels($actions);

        $code = '';

        $this->localVariables = [];

        $yields = new SharedStack();

        if ($comment) {
            $code .= "\t\t// $comment\n";
        }

        /** @var Action $action */
        foreach ($actions as $action)  {
            $type = $action->getType();

            if ($type->isAppendSingleLevel()) {
                $code .= "\n";
            }

            $code .= "\t\t";

            $level = $action->getLevel();
            $yieldCount = $yields->count();

            if ($isYield = $type->isYield($action)) {
                $yields->push($level);
            }

            if ($type->isCloseLevel() || $type->isAppendMultipleLevel()) {
                $level -= 1;
            }

            if ($type->isCloseLevel()) {
                if ($yields) {
                    while ($yields->peek() > $level) {
                        $yields->pop();

                        $code .= "\t\t";
                        $code .= Str::repeat("\t", $level + $yields->count());
                        $code .= "\n});";
                    }
                }
            }

            $code .= Str::repeat("\t", $level + $yieldCount);

            $code .= $convertedCode = $action->getType()->convertToCode($action, $this);

            if ($isYield) {
                $locals = Items::keys($this->getLocalVariables());
                $locals[] = 'event';
                $uses = $locals ? '$' . Str::join($locals, ', $') : '';

                $code .= " function () use ($uses) {";
            }

            if ($type->isAppendMultipleLevel() || $type->isAppendSingleLevel() || $type->isCloseLevel() || $isYield) {
                $code .= "\n";

                if ($type->isCloseLevel()) {
                    $code .= "\n";
                }
            } else {
                if ($convertedCode) {
                    $code .= ";\n";
                } else {
                    $code .= ";";
                }
            }
        }

        if ($yields->count()) {
            while ($yields->count()) {
                $yields->pop();

                $code .= "\t\t";
                $code .= Str::repeat("\t", $yields->count());
                $code .= "});\n";
            }
        }

        if ($endComment) {
            $code .= "\t\t// $endComment\n";
        }

        return $code;
    }

    public function getImports(array $actions)
    {
        $imports = Flow::of([]);

        /** @var Action $action */
        foreach ($actions as $action) {
            $imports = $imports->append($action->imports());
        }

        return $imports->withKeys()->toArray();
    }

    public function compile($file, $outputFile = null)
    {
        if (!$outputFile) {
            $outputFile = $file;
        }

        $phpParser = new PhpParser(FileUtils::get($file));

        $imports = Flow::of([]);

        /** @var DomElement $domClass */
        foreach ($this->document->findAll('/root/class') as $domClass) {
            $className = $domClass->getAttribute('name');

            /** @var DomElement $domMethod */
            foreach ($domClass->findAll("/root/class[@name='$className']/method") as $domMethod) {
                $methodName = $domMethod->getAttribute('name');

                $code = '';
                $actions = [];

                foreach ($domMethod->findAll('*') as $domAction) {
                    $action = $this->manager->buildAction($domAction);
                    $actions[] = $action;

                    $imports = $imports->append($action->imports());
                }

                $code = $this->compileActions($className, $methodName, $actions);

                $phpParser->appendToMethod($className, $methodName, $code);
            }
        }

        $imports = $imports->withKeys()->toArray();

        if ($imports) {
            $phpParser->addUseImports($imports);
        }

        FileUtils::put($outputFile, $phpParser->getContent());
    }
}