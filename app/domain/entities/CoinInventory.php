<?php

namespace app\domain\entities;

use Exception;

class CoinInventory
{
  private array $coins = [];


  /**
   * @param Coin $coin
   * @param int $quantity
   * @return void
   */
  public function addCoins(Coin $coin, int $quantity = 1) :void
  {
    $value = $coin->value;
    $this->coins[$value] = (isset($this->coins[$value]) ?? 0) + $quantity;
  }


  /**
   * @return void
   */
  public function emptyInventory() : void
  {
    $this->coins = [];
  }


  /**
   * @return int
   */
  public function getTotalAmount(): int
  {
    return array_sum($this->coins);
  }


  /**
   * @param CoinInventory $anotherInventory
   * @return void
   */
  public function absorbCash(CoinInventory $anotherInventory) :void
  {
    $newCoins = $anotherInventory->getCoins();

    foreach ($newCoins as $value => $quantity) {
      $this->coins[$value] = ($this->coins[$value] ?? 0) + $quantity;
    }

    $anotherInventory->emptyInventory();
  }


  /**
   * @param array $values
   * @return void
   * @throws Exception
   */
  public function decreaseFromValuesArray(array $values) :void
  {
    foreach ($values as $value)
    {
      if($this->coins[$value] > 0)
      {
        $this->coins[$value] -= 1;
      }
      else
      {
        throw new Exception('Cannot remove a '.$value. ' coin from inventory');
      }

    }
  }


  public function getCoins(): array
  {
    return $this->coins;
  }
}