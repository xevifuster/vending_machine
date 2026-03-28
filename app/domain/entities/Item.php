<?php

namespace app\domain\entities;

class Item
{

  private ItemName $name;
  private float $price;

  /**
   * @param ItemName $name
   * @param $price
   */
  public function __construct(ItemName $name, $price)
  {
    $this->name = $name;
    $this->price = $price;
  }


  /**
   * @return string
   */
  public function getName(): string
  {
    return $this->name->value;
  }


  /**
   * @return int
   */
  public function getPrice(): float
  {
    return $this->price;
  }
}