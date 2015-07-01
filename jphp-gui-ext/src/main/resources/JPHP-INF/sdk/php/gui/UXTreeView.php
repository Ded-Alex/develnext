<?php
namespace php\gui;

/**
 * Class UXTreeView
 * @package php\gui
 */
class UXTreeView extends UXControl
{
    /**
     * @var bool
     */
    public $editable = false;

    /**
     * @var UXTreeItem
     */
    public $root;

    /**
     * @var bool
     */
    public $rootVisible = true;

    /**
     * @var double|int
     */
    public $fixedCellSize;

    /**
     * @readonly
     * @var int
     */
    public $expandedItemCount;

    /**
     * @var bool
     */
    public $multipleSelection = false;

    /**
     * @var UXTreeItem[]
     */
    public $selectedItems = [];

    /**
     * @var int[]
     */
    public $selectedIndexes = [];

    /**
     * @var UXTreeItem
     */
    public $focusedItem = null;

    /**
     * @param int $index
     * @return UXTreeItem
     */
    public function getTreeItem($index)
    {
    }

    /**
     * @param UXTreeItem $item
     * @return int
     */
    public function getTreeItemIndex(UXTreeItem $item)
    {
    }

    /**
     * @param UXTreeItem $item
     * @return int
     */
    public function getTreeItemLevel(UXTreeItem $item)
    {
    }

    /**
     * @param UXTreeItem $item
     * @return bool
     */
    public function isTreeItemFocused(UXTreeItem $item)
    {
    }

    /**
     * @param UXTreeItem $item
     */
    public function edit(UXTreeItem $item)
    {
    }

    /**
     * @param UXTreeItem $item
     */
    public function scrollTo(UXTreeItem $item)
    {
    }
}