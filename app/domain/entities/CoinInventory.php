<?php

namespace app\domain\entities;

use Exception;

class CoinInventory
{
  private array $coins = [];


  public function resetInventory(array $coins) : void
  {
    $this->coins = [];

    foreach ($coins as $coinValue => $quantity)
    {
      $coinEnum = Coin::from($coinValue);
      $this->addCoins($coinEnum, $quantity);
    }
  }

  /**
   * @param Coin $coin
   * @param int $quantity
   * @return void
   */
  public function addCoins(Coin $coin, int $quantity = 1) : void
  {
    $value = $coin->value;
    $this->coins[$value] = ($this->coins[$value] ?? 0) + $quantity;
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
    $total = 0;
    foreach ($this->coins as $coinValue => $quantity) {
      $total += $coinValue * $quantity;
    }
    return $total;
  }


  /**
   * @param CoinInventory $anotherInventory
   * @return void
   */
  public function absorbCash(CoinInventory $anotherInventory) :void
  {
    $newCoins = $anotherInventory->getCoins();

    foreach ($newCoins as $value => $quantity)
    {
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


  /**
   * @param array $coins
   * @return void
   * @throws Exception
   */
  public function decrease(array $coins): void
  {
    foreach ($coins as $coin)
    {
      $value = $coin->value;
      if (($this->coins[$value] ?? 0) > 0)
      {
        $this->coins[$value] -= 1;
      }
      else
      {
        throw new \Exception("Cannot remove a coin of value {$value} from inventory");
      }
    }
  }


  public function getCoins(): array
  {
    return $this->coins;
  }
}