<?php

namespace test\unit\domain\entities;

use app\domain\entities\Coin;
use app\domain\entities\CoinInventory;
use app\domain\entities\Item;
use app\domain\entities\ItemInventory;
use app\domain\entities\ItemName;
use app\domain\entities\VendingMachine;
use app\domain\services\ChangeCalculatorDomService;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VendingMachineTest extends TestCase
{
  private CoinInventory&MockObject $operationCoins;
  private ItemInventory&MockObject $itemInventory;
  private CoinInventory&MockObject $coinInventory;
  private ChangeCalculatorDomService&MockObject $changeCalculator;
  private VendingMachine $sut;

  protected function setUp(): void
  {
    $this->operationCoins = $this->createMock(CoinInventory::class);
    $this->itemInventory = $this->createMock(ItemInventory::class);
    $this->coinInventory = $this->createMock(CoinInventory::class);
    $this->changeCalculator = $this->createMock(ChangeCalculatorDomService::class);

    $this->sut = new VendingMachine($this->operationCoins, $this->itemInventory, $this->coinInventory,
      $this->changeCalculator);
  }

  /**
   * @method resetConfiguration
   * @with validConfiguration
   * @should resetAllInventories
   */
  public function test_resetConfiguration_validConfiguration_resetAllInventories(): void
  {
    $opCoins = [5 => 10];
    $coins = [100 => 10, 25 => 10];
    $items = ['WATER' => ['quantity' => 5, 'price' => 0.65]];

    $this->operationCoins->expects($this->once())
      ->method('resetInventory')
      ->with($opCoins);

    $this->coinInventory->expects($this->once())
      ->method('resetInventory')
      ->with($coins);

    $this->itemInventory->expects($this->once())
      ->method('resetInventory')
      ->with($items);

    $this->sut->resetConfiguration($opCoins, $coins, $items);
  }


  /**
   * @method insertCoin
   * @with coin
   * @should addCoinToOperationInventory
   */
  public function test_insertCoins_coin_addCoinToOperationInventory(): void
  {
    $this->operationCoins->expects($this->once())
      ->method('addCoins')
      ->with(Coin::ONE_EURO);

    $this->sut->insertCoin(Coin::ONE_EURO);
  }


  /**
   * @method moneyRefund
   * @with coinsInserted
   * @should returnCoinsAndEmptyInventory
   */
  public function test_moneyRefund_coinsInserted_returnCoinsAndEmptyInventory(): void
  {
    $this->operationCoins->expects($this->once())
      ->method('getCoins')
      ->willReturn([100 => 2, 25 => 1]);

    $this->operationCoins->expects($this->once())
      ->method('emptyInventory');

    $result = $this->sut->moneyRefund();

    $this->assertEquals([100 => 2, 25 => 1], $result);
  }

  /**
   * @method moneyRefund
   * @with noCoinsInserted
   * @should returnEmptyArray
   */
  public function test_moneyRefund_noCoinsInserted_returnEmptyArray(): void
  {
    $this->operationCoins->expects($this->once())
      ->method('getCoins')
      ->willReturn([]);

    $this->operationCoins->expects($this->once())
      ->method('emptyInventory');

    $result = $this->sut->moneyRefund();

    $this->assertEmpty($result);
  }


  /**
   * @method sellItem
   * @with notEnoughMoney
   * @should throwException
   */
  public function test_sellItem_notEnoughMoney_throwException(): void
  {
    $item = $this->createMock(Item::class);

    $this->itemInventory->expects($this->once())
      ->method('getItem')
      ->willReturn($item);

    $item->expects($this->once())
      ->method('getPrice')
      ->willReturn(1.50);

    $this->operationCoins->expects($this->once())
      ->method('getTotalAmount')
      ->willReturn(100);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Not enough money to get this item');

    $this->itemInventory->expects($this->never())
      ->method('decreaseStock');

    $this->coinInventory->expects($this->never())
      ->method('absorbCash');

    $this->operationCoins->expects($this->never())
      ->method('emptyInventory');

    $this->changeCalculator->expects($this->never())
      ->method('calculateChange');

    $this->sut->sellItem(ItemName::SODA);
  }

  /**
   * @method sellItem
   * @with exactAmountNoChange
   * @should returnItemWithEmptyChange
   */
  public function test_sellItem_exactAmountNoChange_returnItemWithEmptyChange(): void
  {
    $item = $this->createMock(Item::class);

    $this->itemInventory->expects($this->once())
      ->method('getItem')
      ->willReturn($item);

    $item->expects($this->once())
      ->method('getPrice')
      ->willReturn(1.00);

    $this->operationCoins->expects($this->once())
      ->method('getTotalAmount')
      ->willReturn(100);

    $this->itemInventory->expects($this->once())
      ->method('decreaseStock')
      ->with(ItemName::JUICE->value);

    $this->coinInventory->expects($this->once())
      ->method('absorbCash')
      ->with($this->identicalTo($this->operationCoins));

    $this->operationCoins->expects($this->once())
      ->method('emptyInventory');

    $this->changeCalculator->expects($this->once())
      ->method('calculateChange')
      ->with(0, $this->identicalTo($this->coinInventory))
      ->willReturn([]);

    $this->coinInventory->expects($this->once())
      ->method('decrease')
      ->with([]);

    $result = $this->sut->sellItem(ItemName::JUICE);

    $this->assertEquals($item, $result['item']);
    $this->assertEmpty($result['change']);
  }

  /**
   * @method sellItem()
   * @with moreMoneyThanPrice
   * @should returnItemWithChangeAndDecreaseInventory
   */
  public function test_sellItem_moreMoneyThanPrice_returnItemWithChangeAndDecreaseInventory(): void
  {
    $item = $this->createMock(Item::class);
    $change = [Coin::TWENTY_FIVE, Coin::TEN];

    $this->itemInventory->expects($this->once())
      ->method('getItem')
      ->willReturn($item);

    $item->expects($this->once())
      ->method('getPrice')
      ->willReturn(0.65);

    $this->operationCoins->expects($this->once())
      ->method('getTotalAmount')
      ->willReturn(100);

    $this->itemInventory->expects($this->once())
      ->method('decreaseStock')
      ->with(ItemName::WATER->value);

    $this->coinInventory->expects($this->once())
      ->method('absorbCash');

    $this->operationCoins->expects($this->once())
      ->method('emptyInventory');

    $this->changeCalculator->expects($this->once())
      ->method('calculateChange')
      ->with(35, $this->identicalTo($this->coinInventory))
      ->willReturn($change);

    $this->coinInventory->expects($this->once())
      ->method('decrease')
      ->with($change);

    $result = $this->sut->sellItem(ItemName::WATER);

    $this->assertEquals($item, $result['item']);
    $this->assertEquals($change, $result['change']);
  }

}