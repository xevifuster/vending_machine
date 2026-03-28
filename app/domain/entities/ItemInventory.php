<?php

namespace app\domain\entities;

use Exception;

class ItemInventory
{
  private array $items = [];


  public function resetInventory($items)
  {
    $this->items = [];
    foreach ($items as $itemName => $itemData)
    {
      $itemEnum = ItemName::from($itemName);
      $item = new Item($itemEnum, $itemData['price']);

      $this->addItem($item, $itemData['quantity']);
    }
  }

  /**
   * @param Item $item
   * @param int $quantity
   * @return void
   */
  public function addItem(Item $item, int $quantity): void
  {
    $this->items[$item->getName()] = [
      'item' => $item,
      'quantity' => $quantity,
    ];
  }

  /**
   * @param string $itemName
   * @return Item
   */
  public function getItem(string $itemName): Item
  {
    return $this->items[$itemName]['item'];
  }


  /**
   * @param string $name
   * @return bool
   */
  public function hasStock(string $name): bool
  {
    return $this->items[$name]['quantity'] > 0;
  }


  /**
   * @param string $name
   * @return void
   * @throws Exception
   */
  public function decreaseStock(string $name): void
  {
    if (!$this->hasStock($name)) {
      throw new Exception('Item out of stock');
    }

    $this->items[$name]['quantity']--;
  }


  /**
   * @return array
   */
  public function getItems() : array
  {
    return $this->items;
  }
}