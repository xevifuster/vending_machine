<?php

namespace app\domain\repositories;

use app\domain\entities\VendingMachine;

interface IVendingMachineRepository
{
  public function load(): VendingMachine;

  public function save(VendingMachine $vendingMachine): void;

}