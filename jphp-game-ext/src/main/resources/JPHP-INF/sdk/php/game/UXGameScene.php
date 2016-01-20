<?php
namespace php\game;
use php\gui\layout\UXAnchorPane;

/**
 * Class UXGameScene
 * @package php\game
 */
class UXGameScene
{
    /**
     * @var array [x, y]
     */
    public $gravity = [0.0, 0.0];

    /**
     * UXGameScene constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param UXGameEntity $entity
     */
    public function add(UXGameEntity $entity)
    {
    }

    /**
     * @param UXGameEntity $entity
     */
    public function remove(UXGameEntity $entity)
    {
    }

    public function play()
    {
    }

    public function pause()
    {
    }
}