<?php
namespace php\game;

use php\gui\UXNode;
use php\gui\UXParent;

/**
 * Class UXGameObject
 * @package php\game
 */
class UXGameEntity
{
    /**
     * @var string STATIC, DYNAMIC, KINEMATIC
     */
    public $bodyType = 'STATIC';

    /**
     * If null, using scene gravity
     * @var array|null [x, y]
     */
    public $gravity = null;

    /**
     * @readonly
     * @var UXGameScene
     */
    public $gameScene = null;

    /**
     * @var array
     */
    public $velocity = [0, 0];

    /**
     * @param string $entityType
     * @param UXNode $node
     */
    public function __construct($entityType, UXNode $node)
    {
    }
}