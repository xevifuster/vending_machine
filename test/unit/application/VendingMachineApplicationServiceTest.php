<?php

namespace test\unit\application;

use app\application\VendingMachineApplicationService;
use app\domain\entities\Coin;
use app\domain\entities\Item;
use app\domain\entities\ItemName;
use app\domain\entities\VendingMachine;
use app\domain\repositories\IVendingMachineRepository;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VendingMachineApplicationServiceTest extends TestCase
{
  private IVendingMachineRepository&MockObject $repository;
  private VendingMachine&MockObject $vendingMachine;
  private $sut;

  protected function setUp(): void
  {
    $this->repository = $this->createMock(IVendingMachineRepository::class);
    $this->vendingMachine = $this->createMock(VendingMachine::class);

    $this->repository->method('load')->willReturn($this->vendingMachine);
    $this->sut = new VendingMachineApplicationService($this->repository);
  }


  /**
   * @method handle
   * @with insertCoinCommand
   * @should throwExceptionOnInvalidCoin
   */
  public function test_handle_insertCoinCommand_throwExceptionOnInvalidCoin(): void
  {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Invalid coin');

    $this->vendingMachine->expects($this->never())
      ->method('insertCoin');

    $this->repository->expects($this->never())
      ->method('save');

    $this->sut->handle([
      'command' => 'insertCoin',
      'param' => 0.50,
    ]);
  }


  /**
   * @method handle
   * @with insertCoinCommand
   * @should correctInnerCalls
   */
  public function test_handle_insertCoinCommand_correctInnerCalls(): void
  {
    $this->vendingMachine->expects($this->once())
      ->method('insertCoin')
      ->with(Coin::TWENTY_FIVE);

    $this->repository->expects($this->once())
      ->method('save')
      ->with($this->identicalTo($this->vendingMachine));

    $this->sut->handle([
      'command' => 'insertCoin',
      'param' => 0.25,
    ]);
  }


  /**
   * @method handle
   * @with returnCoinsCommand
   * @should returnSortedCoins
   */
  public function test_handle_returnCoinsCommand_returnSortedCoins(): void
  {
    $this->vendingMachine->expects($this->once())
      ->method('moneyRefund')
      ->willReturn([100 => 2, 25 => 1, 5 => 1]);

    $this->repository->expects($this->once())
      ->method('save')
      ->with($this->identicalTo($this->vendingMachine));

    $result = $this->sut->handle(['command' => 'returnCoins']);

    $this->assertEquals([1.00, 1.00, 0.25, 0.05], $result);
  }


  /**
   * @method handle
   * @with returnCoinsCommandAndNoCoinsInSystem
   * @should returnEmptyArray
   */
  public function test_handle_returnCoinsCommandAndNoCoinsInSystem_returnEmptyArrayWhenNoCoins(): void
  {
    $this->vendingMachine->expects($this->once())
      ->method('moneyRefund')
      ->willReturn([]);

    $this->repository->expects($this->once())
      ->method('save')
      ->with($this->identicalTo($this->vendingMachine));

    $result = $this->sut->handle(['command' => 'returnCoins']);

    $this->assertEquals([], $result);
  }


  /**
   * @method handle
   * @with resetMachineCommand
   * @should callResetConfigurationWithParsedData
   */
  public function test_handle_resetMachineCommand_callResetConfigurationWithParsedData(): void
  {
    $json = '{"coins":{"0.05":10,"0.10":5,"0.25":10,"1":10},"items":{"WATER":{"quantity":5,"price":0.65},
    "JUICE":{"quantity":10,"price":1},"SODA":{"quantity":15,"price":1.5}}}';

    $preparedCoins = [5 => 10, 10 => 5, 25 => 10, 100 => 10];
    $preparedItems = [
      'WATER' => ['quantity' => 5, 'price' => 0.65],
      'JUICE' => ['quantity' => 10, 'price' => 1.00],
      'SODA' => ['quantity' => 15, 'price' => 1.50]
    ];

    $this->vendingMachine->expects($this->once())
      ->method('resetConfiguration')
      ->with([], $preparedCoins, $preparedItems);

    $this->repository->expects($this->once())
      ->method('save')
      ->with($this->identicalTo($this->vendingMachine));

    $this->sut->handle(['command' => 'resetMachine', 'param' => $json]);
  }


  /**
   * @method handle
   * @with selectItemCommand
   * @should callSellItemReturningNoChange
   */
  public function test_handle_selectItemCommand_callSellItemReturningNoChange(): void
  {
    $item = $this->createMock(Item::class);

    $this->vendingMachine->expects($this->once())
      ->method('sellItem')
      ->with($this->equalTo(ItemName::WATER))
      ->willReturn(['item' => $item, 'change' => []]);

    $item->expects($this->once())
      ->method('getName')
      ->willReturn(ItemName::WATER->value);

    $this->repository->expects($this->once())
      ->method('save')
      ->with($this->identicalTo($this->vendingMachine));

    $result = $this->sut->handle(['command' => 'selectItem', 'param' => 'WATER']);

    $this->assertEquals([ItemName::WATER->value], $result);
  }


  /**
   * @method handle
   * @with selectItemCommand
   * @should callSellItemReturningChange
   */
  public function test_handle_selectItemCommand_callSellItemReturningChange(): void
  {
    $item = $this->createMock(Item::class);
    $coin1 = Coin::coinCentsIntValue(0.25);
    $coin2 = Coin::coinCentsIntValue(0.1);

    $this->vendingMachine->expects($this->once())
      ->method('sellItem')
      ->with($this->equalTo(ItemName::WATER))
      ->willReturn(['item' => $item, 'change' => [$coin1, $coin2]]);

    $item->expects($this->once())
      ->method('getName')
      ->willReturn(ItemName::WATER->value);

    $this->repository->expects($this->once())
      ->method('save')
      ->with($this->identicalTo($this->vendingMachine));

    $result = $this->sut->handle(['command' => 'selectItem', 'param' => 'WATER']);

    $this->assertEquals([ItemName::WATER->value, 0.25, 0.1], $result);
  }


  /**
   * @method handle
   * @with unknownCommand
   * @should throwException
   */
  public function test_handle_unknownCommand_throwException(): void
  {
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Unknown command');

    $this->repository->expects($this->never())
      ->method('save');

    $this->sut->handle(['command' => 'hitTheMachine']);
  }
}
