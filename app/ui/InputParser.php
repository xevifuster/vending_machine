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
      return ['command' => 'resetMachine'];
    }
    else
    {
      throw new Exception('Unknown input type');
    }
  }

}