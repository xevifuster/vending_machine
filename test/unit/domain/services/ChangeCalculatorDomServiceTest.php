<?php

namespace test\unit\domain\services;

use app\domain\entities\Coin;
use app\domain\entities\CoinInventory;
use app\domain\services\ChangeCalculatorDomService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangeCalculatorDomServiceTest extends TestCase
{
  private CoinInventory&MockObject $coinInventory;
  private ChangeCalculatorDomService $sut;

  protected function setUp(): void
  {
    $this->coinInventory = $this->createMock(CoinInventory::class);
    $this->sut = new ChangeCalculatorDomService();
  }


  /**
   * @method calculateChange
   * @with exactChangeSingleCoinType
   * @should callGetCoinsOnceAndReturnExactCoins
   */
  public function test_calculateChange_exactChangeSingleCoinType_callGetCoinsOnceAndReturnExactCoins(): void
  {
    $this->coinInventory->expects($this->once())
      ->method('getCoins')
      ->willReturn([25 => 5]);

    $result = $this->sut->calculateChange(50, $this->coinInventory);

    $this->assertCount(2, $result);
    $this->assertEquals(Coin::TWENTY_FIVE, $result[0]);
    $this->assertEquals(Coin::TWENTY_FIVE, $result[1]);
  }

  /**
   * @method calculateChange
   * @with exactChangeMultipleCoinTypes
   * @should returnMixedCoins
   */
  public function test_calculateChange_exactChangeMultipleCoinTypes_returnMixedCoins(): void
  {
    $this->coinInventory->expects($this->once())
      ->method('getCoins')
      ->willReturn([25 => 1, 10 => 2, 5 => 3]);

    $result = $this->sut->calculateChange(35, $this->coinInventory);

    $this->assertCount(2, $result);
    $this->assertEquals(Coin::TWENTY_FIVE, $result[0]);
    $this->assertEquals(Coin::TEN, $result[1]);
  }

  /**
   * @method calculateChange
   * @with noExactChangePossible
   * @should returnBestApproximationCombination
   */
  public function test_calculateChange_noExactChangePossible_returnBestApproximationCombination(): void
  {
    $this->coinInventory->expects($this->once())
      ->method('getCoins')
      ->willReturn([25 => 1, 5 => 1]);

    $result = $this->sut->calculateChange(40, $this->coinInventory);

    $this->assertCount(2, $result);
    $this->assertEquals(Coin::TWENTY_FIVE, $result[0]);
    $this->assertEquals(Coin::FIVE, $result[1]);
  }

  /**
   * @method calculateChange
   * @with emptyCoinInventory
   * @should returnEmptyArray
   */
  public function test_calculateChange_emptyCoinInventory_returnEmptyArray(): void
  {
    $this->coinInventory->expects($this->once())
      ->method('getCoins')
      ->willReturn([]);

    $result = $this->sut->calculateChange(50, $this->coinInventory);

    $this->assertEmpty($result);
  }

  /**
   * @method calculateChange
   * @with zeroAmount
   * @should returnEmptyArray
   */
  public function test_calculateChange_zeroAmount_returnEmptyArray(): void
  {
    $this->coinInventory->expects($this->once())
      ->method('getCoins')
      ->willReturn([100 => 5]);

    $result = $this->sut->calculateChange(0, $this->coinInventory);

    $this->assertEmpty($result);
  }

  /**
   * @method calculateChange
   * @with coinsValueBiggerThanAmountNeeded
   * @should returnEmptyArray
   */
  public function test_calculateChange_coinsValueBiggerThanAmountNeeded_returnEmptyArray(): void
  {
    $this->coinInventory->expects($this->once())
      ->method('getCoins')
      ->willReturn([100 => 2]);

    $result = $this->sut->calculateChange(50, $this->coinInventory);

    $this->assertEmpty($result);
  }

  /**
   * @method calculateChange
   * @with multipleExactSolutionsAvailable
   * @should returnLeastCoinsPossible
   */
  public function test_calculateChange_multipleExactSolutionsAvailable_returnLeastCoinsPossible(): void
  {
    $this->coinInventory->expects($this->once())
      ->method('getCoins')
      ->willReturn([10 => 3, 5 => 4]);

    $result = $this->sut->calculateChange(15, $this->coinInventory);

    $this->assertCount(2, $result);
    $this->assertEquals(Coin::TEN, $result[0]);
    $this->assertEquals(Coin::FIVE, $result[1]);
  }
}
