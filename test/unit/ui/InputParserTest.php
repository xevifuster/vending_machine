<?php

namespace test\unit\ui;

use app\ui\InputParser;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InputParserTest extends TestCase
{
  private InputParser $sut;

  protected function setUp(): void
  {
    $this->sut = new InputParser();
  }

  /**
   * @return string[]
   */
  public static function validCoinsData(): array
  {
    return [
      ['0.05'],
      ['0.10'],
      ['0.25'],
      ['1']
    ];
  }


  /**
   * @method parse
   * @with validCoin
   * @should returnInsertCoinCommand
   */
  #[DataProvider('validCoinsData')]
  public function test_parse_validCoin_returnInsertCoinCommand($coinValue): void
  {
    $result = $this->sut->parse($coinValue);

    $this->assertEquals('insertCoin', $result['command']);
    $this->assertEquals(floatval($coinValue), $result['param']);
  }


  /**
   * @return string[]
   */
  public static function validGetCommandsData(): array
  {
    return [
      ['GET-WATER', 'WATER'],
      ['GET-Juice', 'JUICE'],
      ['get-soda', 'SODA']
    ];
  }

  /**
   * @method parse
   * @with validGetCommand
   * @should returnSelectItemCommand
   */
  #[DataProvider('validGetCommandsData')]
  public function test_parse_validGetCommand_returnSelectItemCommand($getCommand, $param): void
  {
    $result = $this->sut->parse($getCommand);

    $this->assertEquals('selectItem', $result['command']);
    $this->assertEquals($param, $result['param']);
  }


  /**
   * @method parse
   * @with returnCoinCommand
   * @should returnReturnCoinsCommand
   */
  public function test_parse_returnCoinCommand_returnReturnCoinsCommand(): void
  {
    $result = $this->sut->parse('RETURN-COIN');

    $this->assertEquals('returnCoins', $result['command']);
    $this->assertArrayNotHasKey('param', $result);
  }


  /**
   * @method parse
   * @with serviceCommand
   * @should returnResetMachineCommandWithJsonParam
   */
  public function test_parse_serviceCommand_returnResetMachineCommandWithJsonParam(): void
  {
    $defaultConfigurationJson = '{"coins":{"0.05":10,"0.1":5,"0.25":10,"1":10},"items":{"WATER":
      {"quantity":5,"price":0.65},"JUICE":{"quantity":10,"price":1},"SODA":{"quantity":15,"price":1.5}}}';
    $result = $this->sut->parse('SERVICE');

    $this->assertEquals('resetMachine', $result['command']);
    $this->assertEquals($defaultConfigurationJson, $result['param']);
  }


  /**
   * @method parse
   * @with invalidCoin
   * @should throwInvalidCoinException
   */
  public function test_parse_invalidNumericCoin_throwInvalidCoinException(): void
  {
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Invalid coin');

    $this->sut->parse('0.50');
  }

  /**
   * @method parse
   * @with unknownStringCommand
   * @should throwUnknownCommandException
   */
  public function test_parse_unknownStringCommand_throwUnknownCommandException(): void
  {
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Unknown command');

    $this->sut->parse('SMASH-THE-MACHINE');
  }

  /**
   * @method parse
   * @with emptyString
   * @should throwUnknownCommandException
   */
  public function test_parse_emptyString_throwUnknownCommandException(): void
  {
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Unknown command');

    $this->sut->parse('');
  }
}