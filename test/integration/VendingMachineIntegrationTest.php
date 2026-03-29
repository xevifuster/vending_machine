<?php

namespace test\integration;

use app\application\VendingMachineApplicationService;
use app\infrastructure\persistence\json\JsonFileVMRepository;
use app\ui\InputParser;
use PHPUnit\Framework\TestCase;

class VendingMachineIntegrationTest extends TestCase
{
  private InputParser $parser;
  private string $testJsonsPath;
  private string $currentJsonFile;
  private array $masterConfigurations;

  protected function setUp(): void
  {
    $this->parser = new InputParser();
    $this->testJsonsPath = __DIR__ . '/test_jsons/';

    $masterContent = file_get_contents($this->testJsonsPath . 'master_configurations.json');
    $this->masterConfigurations = json_decode($masterContent, true);
  }

  protected function tearDown(): void
  {
    if (isset($this->currentJsonFile) && file_exists($this->currentJsonFile)) {
      unlink($this->currentJsonFile);
    }
  }


  private function runSequence(string $configName, array $inputs): mixed
  {
    $json = json_encode($this->masterConfigurations[$configName], JSON_PRETTY_PRINT);
    $this->currentJsonFile = $this->testJsonsPath . $configName . '.json';
    file_put_contents($this->currentJsonFile, $json);

    $repository = new JsonFileVMRepository($this->currentJsonFile);
    $service = new VendingMachineApplicationService($repository);

    $result = null;

    foreach ($inputs as $input)
    {
      $command = $this->parser->parse($input);
      $result = $service->handle($command);
    }

    return $result;
  }


  /**
   * @method handle
   * @with buySodaWithExactAmount
   * @should returnSoda
   */
  public function test_handle_buySodaWithExactAmount_returnSoda(): void
  {
    $result = $this->runSequence('exact_purchase', ['1', '0.25', '0.25', 'GET-SODA']);

    $this->assertEquals(['SODA'], $result);
  }


  /**
   * @method handle
   * @with refundCoins
   * @should returnInsertedCoinsSorted
   */
  public function test_handle_refundCoins_returnInsertedCoinsSorted(): void
  {
    $result = $this->runSequence('refund', ['0.10', '1', '0.25', 'RETURN-COIN']);

    $this->assertEquals([1, 0.25, 0.10], $result);
  }


  /**
   * @method handle
   * @with buyWaterWithCorrectChange
   * @should returnWaterAndChange
   */
  public function test_handle_buyWaterWithCorrectChange_returnWaterAndChange(): void
  {
    $result = $this->runSequence('change_purchase', ['1', 'GET-WATER']);

    $this->assertEquals('WATER', $result[0]);
    $this->assertEquals(0.25, $result[1]);
    $this->assertEquals(0.10, $result[2]);
  }


  /**
   * @method handle
   * @with notEnoughMoneyToPurchaseSelectedItem
   * @should throwException
   */
  public function test_handle_notEnoughMoneyToPurchaseSelectedItem_throwException(): void
  {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Not enough money to get this item');

    $this->runSequence('exact_purchase', ['1', 'GET-SODA']);
  }


  /**
   * @method handle
   * @with buyItemWithInsufficientChangeInMachine
   * @should returnWaterAndBestApproximationChange
   */
  public function test_handle_buyItemWithInsufficientChangeInMachine_returnWaterAndBestApproximationChange(): void
  {
    $result = $this->runSequence('insufficient_change', ['1', 'GET-WATER']);

    $this->assertEquals('WATER', $result[0]);
    $this->assertEquals(0.25, $result[1]);
    $this->assertEquals(0.05, $result[2]);
  }


  /**
   * @method handle
   * @with buyItemWithNoSmallCoinsForChange
   * @should returnItemWithoutChange
   */
  public function test_handle_buyItemWithNoSmallCoinsForChange_returnItemWithoutChange(): void
  {
    $result = $this->runSequence('no_change_coins_available', ['1', '1', 'GET-SODA']);

    $this->assertEquals(['SODA'], $result);
  }


  /**
   * @method handle
   * @with serviceResetThenBuy
   * @should resetAndReturnJuke
   */
  public function test_handle_serviceResetThenBuy_resetAndReturnJuice(): void
  {
    $result = $this->runSequence('refund', ['SERVICE', '1', 'GET-JUICE']);

    $this->assertEquals(['JUICE'], $result);
  }


  /**
   * @method handle
   * @with itemOutOfStock
   * @should throwExceptionOnThirdPurchase
   */
  public function test_handle_itemOutOfStock_throwExceptionOnThirdPurchase(): void
  {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Item out of stock');

    $this->runSequence('out_of_stock', ['1', 'GET-WATER', '1', 'GET-WATER', '1', 'GET-WATER']);
  }

}