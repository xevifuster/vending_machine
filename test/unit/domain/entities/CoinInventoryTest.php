<?php

namespace test\unit\domain\entities;

use app\domain\entities\Coin;
use app\domain\entities\CoinInventory;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CoinInventoryTest extends TestCase
{
  private CoinInventory $sut;

  protected function setUp(): void
  {
    $this->sut = new CoinInventory();
  }


  /**
   * @method addCoins
   * @with coinAndDefaultQuantity
   * @should addOneCoin
   */
  public function test_addCoins_coinAndDefaultQuantity_addOneCoin(): void
  {
    $this->sut->addCoins(Coin::TWENTY_FIVE);

    $coins = $this->sut->getCoins();
    $this->assertEquals(1, $coins[25]);
  }

  /**
   * @method addCoins
   * @with coinAndCustomQuantity
   * @should addMultipleCoins
   */
  public function test_addCoins_coinAndCustomQuantity_addMultipleCoins(): void
  {
    $this->sut->addCoins(Coin::ONE_EURO, 3);

    $coins = $this->sut->getCoins();
    $this->assertEquals(3, $coins[100]);
  }

  /**
   * @method addCoins
   * @with sameCoinMultipleTimes
   * @should accumulateQuantity
   */
  public function test_addCoins_sameCoinMultipleTimes_accumulateQuantity(): void
  {
    $this->sut->addCoins(Coin::TWENTY_FIVE, 2);
    $this->sut->addCoins(Coin::TWENTY_FIVE, 3);

    $coins = $this->sut->getCoins();
    $this->assertEquals(5, $coins[25]);
  }

  /**
   * @method addCoins
   * @with differentCoinTypes
   * @should storeEachTypeSeparately
   */
  public function test_addCoins_differentCoinTypes_storeEachTypeSeparately(): void
  {
    $this->sut->addCoins(Coin::TWENTY_FIVE, 2);
    $this->sut->addCoins(Coin::ONE_EURO, 3);
    $this->sut->addCoins(Coin::FIVE, 1);

    $coins = $this->sut->getCoins();

    $this->assertEquals(2, $coins[25]);
    $this->assertEquals(3, $coins[100]);
    $this->assertEquals(1, $coins[5]);
  }


  /**
   * @method getTotalAmount
   * @with multipleCoinsOfDifferentValues
   * @should returnTotalValueInCents
   */
  public function test_getTotalAmount_multipleCoinsOfDifferentValues_returnTotalValueInCents(): void
  {
    $this->sut->addCoins(Coin::ONE_EURO, 2);
    $this->sut->addCoins(Coin::TWENTY_FIVE, 3);
    $this->sut->addCoins(Coin::FIVE, 4);

    $result = $this->sut->getTotalAmount();

    $this->assertEquals(295, $result);
  }

  /**
   * @method getTotalAmount
   * @with emptyInventory
   * @should returnZero
   */
  public function test_getTotalAmount_emptyInventory_returnZero(): void
  {
    $result = $this->sut->getTotalAmount();
    $this->assertEquals(0, $result);
  }

  /**
   * @method getTotalAmount
   * @with singleCoinType
   * @should returnCorrectTotal
   */
  public function test_getTotalAmount_singleCoinType_returnCorrectTotal(): void
  {
    $this->sut->addCoins(Coin::ONE_EURO, 4);

    $result = $this->sut->getTotalAmount();
    $this->assertEquals(400, $result);
  }


  /**
   * @method emptyInventory
   * @with coinsPresent
   * @should clearAllCoins
   */
  public function test_emptyInventory_coinsPresent_clearAllCoins(): void
  {
    $this->sut->addCoins(Coin::ONE_EURO, 2);
    $this->sut->addCoins(Coin::TWENTY_FIVE, 3);

    $this->sut->emptyInventory();

    $result = $this->sut->getCoins();

    $this->assertEmpty($result);
  }

  /**
   * @method emptyInventory
   * @with alreadyEmpty
   * @should remainEmpty
   */
  public function test_emptyInventory_alreadyEmpty_remainEmpty(): void
  {
    $this->sut->emptyInventory();

    $result = $this->sut->getCoins();

    $this->assertEmpty($result);
  }

  /**
   * @method getCoins
   * @with coinsAdded
   * @should returnCoinArray
   */
  public function test_getCoins_coinsAdded_returnCoinArray(): void
  {
    $this->sut->addCoins(Coin::TWENTY_FIVE, 2);
    $this->sut->addCoins(Coin::TEN, 1);

    $coins = $this->sut->getCoins();

    $this->assertEquals(2, $coins[25]);
    $this->assertEquals(1, $coins[10]);
  }

  /**
   * @method getCoins
   * @with emptyInventory
   * @should returnEmptyArray
   */
  public function test_getCoins_emptyInventory_returnEmptyArray(): void
  {
    $this->assertEmpty($this->sut->getCoins());
  }


  /**
   * @method resetInventory
   * @with validCoinData
   * @should clearAndPopulateWithNewCoins
   */
  public function test_resetInventory_validCoinData_clearAndPopulateWithNewCoins(): void
  {
    $this->sut->addCoins(Coin::ONE_EURO, 10);

    $this->sut->resetInventory([5 => 10, 25 => 5]);

    $coins = $this->sut->getCoins();

    $this->assertArrayNotHasKey(100, $coins);
    $this->assertEquals(10, $coins[5]);
    $this->assertEquals(5, $coins[25]);
  }


  /**
   * @method absorbCash
   * @with anotherInventoryWithCoins
   * @should addCoinsAndEmptySource
   */
  public function test_absorbCash_anotherInventoryWithCoins_addCoinsAndEmptySource(): void
  {
    $this->sut->addCoins(Coin::ONE_EURO, 2);

    $otherInventory = $this->createMock(CoinInventory::class);

    $otherInventory->expects($this->once())
      ->method('getCoins')
      ->willReturn([25 => 3, 10 => 1]);

    $otherInventory->expects($this->once())
      ->method('emptyInventory');

    $this->sut->absorbCash($otherInventory);

    $coins = $this->sut->getCoins();
    $this->assertEquals(2, $coins[100]);
    $this->assertEquals(3, $coins[25]);
    $this->assertEquals(1, $coins[10]);
  }

  /**
   * @method absorbCash
   * @with sameCoinTypeInBoth
   * @should accumulateQuantities
   */
  public function test_absorbCash_sameCoinTypeInBoth_accumulateQuantities(): void
  {
    $otherInventory = $this->createMock(CoinInventory::class);

    $this->sut->addCoins(Coin::ONE_EURO, 2);
    $this->sut->addCoins(Coin::TWENTY_FIVE, 1);

    $otherInventory->method('getCoins')
      ->willReturn([100 => 3]);

    $otherInventory->expects($this->once())
      ->method('emptyInventory');

    $this->sut->absorbCash($otherInventory);

    $coins = $this->sut->getCoins();

    $this->assertEquals(5, $coins[100]);
    $this->assertEquals(1, $coins[25]);
  }

  /**
   * @method absorbCash
   * @with emptySourceInventory
   * @should keepCurrentCoins
   */
  public function test_absorbCash_emptySourceInventory_keepCurrentCoins(): void
  {
    $this->sut->addCoins(Coin::ONE_EURO, 2);

    $otherInventory = $this->createMock(CoinInventory::class);

    $otherInventory->method('getCoins')
      ->willReturn([]);

    $otherInventory->expects($this->once())
      ->method('emptyInventory');

    $this->sut->absorbCash($otherInventory);

    $coins = $this->sut->getCoins();
    $this->assertEquals(2, $coins[100]);
  }


  /**
   * @method decrease
   * @with availableCoins
   * @should decrementQuantities
   */
  public function test_decrease_availableCoins_decrementQuantities(): void
  {
    $this->sut->addCoins(Coin::ONE_EURO, 3);
    $this->sut->addCoins(Coin::TWENTY_FIVE, 2);

    $this->sut->decrease([Coin::ONE_EURO, Coin::TWENTY_FIVE]);

    $coins = $this->sut->getCoins();
    $this->assertEquals(2, $coins[100]);
    $this->assertEquals(1, $coins[25]);
  }

  /**
   * @method decrease
   * @with coinNotInInventory
   * @should throwException
   */
  public function test_decrease_coinNotInInventory_throwException(): void
  {
    $this->sut->addCoins(Coin::ONE_EURO, 2);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Cannot remove a coin of value 25 from inventory');

    $this->sut->decrease([Coin::TWENTY_FIVE]);
  }

  /**
   * @method decrease
   * @with emptyArray
   * @should doNothing
   */
  public function test_decrease_emptyArray_doNothing(): void
  {
    $this->sut->addCoins(Coin::ONE_EURO, 2);

    $this->sut->decrease([]);

    $coins = $this->sut->getCoins();

    $this->assertEquals(2, $coins[100]);
  }
}
