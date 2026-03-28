<?php

namespace app\infrastructure\persistence\json;

use app\domain\entities\Coin;
use app\domain\entities\Item;
use app\domain\entities\ItemName;
use app\domain\entities\VendingMachine;
use app\domain\repositories\IVendingMachineRepository;

class JsonFileVMRepository implements IVendingMachineRepository
{

  private string $configFileRoute;

  public function __construct(?string $configFileRoute = null)
  {
    $this->configFileRoute = $configFileRoute ?? __DIR__ . '/../../../storage/VM.json';
  }
  public function load(): VendingMachine
  {

    if (!file_exists($this->configFileRoute)) {
      throw new \Exception('Config file not found');
    }

    $data = json_decode(file_get_contents($this->configFileRoute), true);

    $operationCoinsArray = $data['operation_coins'] ?? [];
    $coinsArray = $data['coins'] ?? [];
    $itemsArray = $data['items'] ?? [];

    $operationCoins = [];
    $coins = [];
    $items = [];

    foreach ($operationCoinsArray as $amount => $quantity)
    {
      $coinEnum = Coin::coinCentsIntValue((float)$amount);
      $operationCoins[$coinEnum->value] = $quantity;
    }

    foreach ($coinsArray as $amount => $quantity)
    {
      $coinEnum = Coin::coinCentsIntValue((float)$amount);
      $coins[$coinEnum->value] = $quantity;
    }

    foreach ($itemsArray as $itemNameStr => $itemData) {
      $itemEnum = ItemName::from($itemNameStr);
      $items[$itemEnum->value] = $itemData;
    }

    $vendingMachine = new VendingMachine();


    $vendingMachine->resetConfiguration($operationCoins, $coins, $items);

    return $vendingMachine;
  }

  public function save(VendingMachine $vendingMachine): void
  {
    $state = [];
    $operationCoins = $vendingMachine->getOperationCoins();

    $operationCoins = $operationCoins->getCoins();

    $operationCoinsArray = [];
    foreach ($operationCoins as $coinValue => $coinQuantity)
    {
      $index = strval($coinValue/100);
      $operationCoinsArray[$index] = $coinQuantity;
    }

    $state['operation_coins'] = $operationCoinsArray;

    $coinInventory = $vendingMachine->getCoinInventory();

    $coinInventoryCoins = $coinInventory->getCoins();

    $coinsArray = [];
    foreach ($coinInventoryCoins as $coinValue => $coinQuantity)
    {
      $index = strval($coinValue/100);
      $coinsArray[$index] = $coinQuantity;
    }

    $state['coins'] = $coinsArray;

    $itemInventory = $vendingMachine->getItemInventory();
    $itemInventoryItems = $itemInventory->getItems();

    $items = [];
    foreach ($itemInventoryItems as $itemInventoryItemName => $itemInventoryItemData)
    {
      $item = $itemInventoryItemData['item'];
      $price = $item->getPrice();
      $items[$itemInventoryItemName] = ["quantity" => $itemInventoryItemData['quantity'], "price" => $price];
    }

    $state['items'] = $items;

    $stateJson = json_encode($state, JSON_PRETTY_PRINT);

    file_put_contents($this->configFileRoute, $stateJson);

  }
}