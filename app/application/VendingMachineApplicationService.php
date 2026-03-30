<?php

namespace app\application;

use app\domain\entities\Coin;
use app\domain\entities\ItemName;
use app\domain\entities\VendingMachine;
use app\domain\repositories\IVendingMachineRepository;
use Exception;


class VendingMachineApplicationService
{

  /** @var IVendingMachineRepository */
  private $repository;

  /** @var  VendingMachine*/
  private $vendingMachine;

  /**
   * @param IVendingMachineRepository $repository
   */
  public function __construct(IVendingMachineRepository $repository)
  {
    $this->repository = $repository;
    $this->vendingMachine = $this->repository->load();
  }


  /**
   * @param array $commandArray
   * @return mixed
   * @throws Exception
   */
  public function handle(array $commandArray)
  {
    return match ($commandArray['command'])
    {
      'insertCoin' => $this->insertCoin($commandArray['param']),
      'selectItem' => $this->selectItem($commandArray['param']),
      'returnCoins' => $this->refund(),
      'resetMachine' => $this->resetMachine($commandArray['param']),
      default => throw new Exception('Unknown command'),
    };
  }

  /**
   * @param $amount
   * @return void
   * @throws Exception
   */
  private function insertCoin($amount): void
  {
    $coin = Coin::coinCentsIntValue($amount);
    $this->vendingMachine->insertCoin($coin);

    $this->saveStatus();
  }

  /**
   * @param $itemName
   * @return array|string[]
   * @throws Exception
   */
  private function selectItem($itemName) : array
  {
    try
    {
      $itemValidatedName = ItemName::from($itemName);
    }
    catch (\ValueError $e)
    {
      throw new Exception('Invalid item');
    }


    $resultArray = $this->vendingMachine->sellItem($itemValidatedName);

    $this->saveStatus();

    $changeValues = array_map(fn(Coin $coin) => $coin->value / 100, $resultArray['change']);

    return array_merge([$resultArray['item']->getName()], $changeValues);
  }


  /**
   * @return array
   */
  private function refund() : array
  {
    $resultCoins = $this->vendingMachine->moneyRefund();

    $result = [];
    foreach ($resultCoins as $value => $count) {
      for ($i = 0; $i < $count; $i++) {
        $result[] = $value / 100;
      }
    }

    rsort($result);

    $this->saveStatus();

    return $result;
  }


  /**
   * @param $configurationJSON
   * @return void
   * @throws Exception
   */
  private function resetMachine($configurationJSON) : void
  {
    $configurationArray = json_decode($configurationJSON, true);
    $coins = [];
    $items = [];

    foreach ($configurationArray['coins'] as $amount => $quantity)
    {
      $coinEnum = Coin::coinCentsIntValue((float)$amount);
      $coins[$coinEnum->value] = $quantity;
    }

    foreach ($configurationArray['items'] as $itemNameStr => $itemData) {
      $itemEnum = ItemName::from($itemNameStr);
      $items[$itemEnum->value] = $itemData;
    }

    $this->vendingMachine->resetConfiguration([], $coins, $items);

    $this->saveStatus();
  }


  /**
   * @return void
   */
  private function saveStatus() : void
  {
    $this->repository->save($this->vendingMachine);
  }

}