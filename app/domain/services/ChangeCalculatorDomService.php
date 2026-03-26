<?php

namespace app\domain\services;

use app\domain\entities\CoinInventory;

class ChangeCalculatorDomService
{

  private ?array $bestExact = null;
  private ?array $bestApprox = null;
  private int $bestApproxTotal = 0;


  /**
   * @param int $amount
   * @param CoinInventory $totalCoins
   * @return array
   */
  public function calculateChange(int $amount, CoinInventory $totalCoins): array
  {
    $coins = $totalCoins->getCoins();
    krsort($coins);

    $this->bestExact = null;
    $this->bestApprox = null;
    $this->bestApproxTotal = 0;

    $this->search($amount, $coins, [], 0);

    if ($this->bestExact !== null)
    {
      return $this->bestExact;
    }

    if ($this->bestApprox !== null)
    {
      return $this->bestApprox;
    }

    return [];
  }


  /**
   * @param int $remaining
   * @param array $coins
   * @param array $current
   * @param int $currentTotal
   * @return void
   */
  private function search(int $remaining, array $coins, array $current, int $currentTotal): void
  {
    if ($remaining === 0)
    {
      $this->bestExact = $current;
      return;
    }

    if ($currentTotal > $this->bestApproxTotal)
    {
      $this->bestApproxTotal = $currentTotal;
      $this->bestApprox = $current;
    }

    foreach ($coins as $coinValue => $quantity)
    {
      if ($quantity <= 0 || $coinValue > $remaining)
      {
        continue;
      }

      if ($this->bestExact !== null)
      {
        return;
      }

      $coins[$coinValue]--;

      $this->search($remaining - $coinValue, $coins, [...$current, $coinValue],$currentTotal + $coinValue);
    }
  }
}