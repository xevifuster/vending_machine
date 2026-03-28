<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use app\application\VendingMachineApplicationService;
use app\infrastructure\persistence\json\JsonFileVMRepository;
use app\ui\InputParser;


echo "----> Vending Machine <-----\n\n";
echo "Valid Commands: 0.05 | 0.10 | 0.25 | 1 | GET-WATER | GET-JUICE | GET-SODA | RETURN-COIN | SERVICE | EXIT\n";
echo "Starting service...\n";

$parser = new InputParser();
$repository = new JsonFileVMRepository();
$vendingMachineService = new VendingMachineApplicationService($repository);

echo "Service ready!\n";

while (true) {

  echo "> ";

  $input = strtoupper(trim(fgets(STDIN)));

  if ($input === 'EXIT')
  {
    echo "Shutting down!\n";
    break;
  }

  try
  {
    $commandArray = $parser->parse($input);
    $result = $vendingMachineService->handle($commandArray);

    if (!empty($result))
    {
      echo "→ " . implode(", ", $result) . "\n";
    }

  }
  catch (\Exception $e)
  {
    echo "Error: " . $e->getMessage() . "\n";
  }
}