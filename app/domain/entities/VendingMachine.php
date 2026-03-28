<?php
namespace app\domain\entities;

use app\domain\services\ChangeCalculatorDomService;
use Exception;

class VendingMachine
{
  private CoinInventory $operationCoins;
  private ItemInventory $itemInventory;

  private CoinInventory $coinInventory;

  private ChangeCalculatorDomService $changeCalculator;


  /**
   * @param CoinInventory|null $operationCoins
   * @param ItemInventory|null $itemInventory
   * @param CoinInventory|null $coinInventory
   * @param ChangeCalculatorDomService|null $changeCalculator
   */
  public function __construct(?CoinInventory $operationCoins = null, ?ItemInventory $itemInventory = null,
                              ?CoinInventory $coinInventory = null, ?ChangeCalculatorDomService $changeCalculator = null)
  {
    $this->operationCoins = $operationCoins ?? new CoinInventory();
    $this->itemInventory = $itemInventory ?? new ItemInventory();
    $this->coinInventory = $coinInventory ?? new CoinInventory();
    $this->changeCalculator = $changeCalculator ?? new ChangeCalculatorDomService();

  }


  /**
   * @param $operationCoins
   * @param $coins
   * @param $items
   * @return void
   */
  public function resetConfiguration($operationCoins, $coins, $items): void
  {
    $this->operationCoins->resetInventory($operationCoins);
    $this->coinInventory->resetInventory($coins);
    $this->itemInventory->resetInventory($items);
  }


  /**
   * @param Coin $coin
   * @param int $quantity
   * @return void
   */
  public function insertCoins(Coin $coin, int $quantity = 1): void
  {
    $this->operationCoins->addCoins($coin, $quantity);
  }


  /**
   * @param Item $item
   * @param int $quantity
   * @return void
   */
  public function insertItem(Item $item, int $quantity = 1): void
  {
    $this->itemInventory->addItem($item, $quantity);
  }

  /**
   * @return array
   */
  public function moneyRefund() : array
  {
    $coins = $this->operationCoins->getCoins();
    $this->operationCoins->emptyInventory();

    return $coins;
  }


  /**
   * @param ItemName $itemName
   * @return array
   * @throws Exception
   */
  public function sellItem(ItemName $itemName): array
  {
    $item = $this->itemInventory->getItem($itemName->value);
    $intPrice = $item->getPrice() * 100;
    $operationAmount = $this->operationCoins->getTotalAmount();

    if ($operationAmount < $intPrice)
    {
      throw new Exception("Not enough money to get this item");
    }


    $this->itemInventory->decreaseStock($itemName->value);
    $this->coinInventory->absorbCash($this->operationCoins);
    $this->operationCoins->emptyInventory();

    $changeAmount = $operationAmount - $intPrice;

    /** @var Coin[] $change */
    $change = $this->changeCalculator->calculateChange($changeAmount, $this->coinInventory);

    $this->coinInventory->decrease($change);

    return [
      'item' => $item,
      'change' => $change,
    ];
  }

  /**
   * @return CoinInventory
   */
  public function getOperationCoins(): CoinInventory
  {
    return $this->operationCoins;
  }

  /**
   * @return ItemInventory
   */
  public function getItemInventory(): ItemInventory
  {
    return $this->itemInventory;
  }


  /**
   * @return CoinInventory
   */
  public function getCoinInventory(): CoinInventory
  {
    return $this->coinInventory;
  }




}