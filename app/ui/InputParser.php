<?php

namespace app\ui;

use Exception;

class InputParser
{

  /**
   * @param string $input
   * @return array|string[]
   * @throws Exception
   */
  public function parse(string $input): array
  {

    if (in_array($input, ['0.05', '0.10', '0.25', '1']))
    {
      return ['command' => 'insertCoin', 'param' => (float)$input];
    }
    else if (str_starts_with($input, 'GET-'))
    {
      return ['command' => 'selectItem', 'param' => substr($input, 4)];
    }
    else if ($input === 'RETURN-COIN')
    {
      return ['command' => 'returnCoins'];
    }
    else if ($input === 'SERVICE')
    {
      return ['command' => 'resetMachine', 'param' => ' {"coins":{"0.05":10,"0.1":5,"0.25":10,"1":10},"items":{"WATER":
      {"quantity":5,"price":0.65},"JUICE":{"quantity":10,"price":1},"SODA":{"quantity":15,"price":1.5}}}'];
    }
    else
    {
      if(is_numeric($input))
      {
        $message = 'Invalid coin';
      }
      else
      {
        $message = 'Unknown command';
      }
      throw new Exception($message);
    }
  }

}