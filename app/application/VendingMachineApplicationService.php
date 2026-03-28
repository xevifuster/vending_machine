<?php

namespace app\application;


use app\domain\entities\VendingMachine;
use app\domain\repositories\IVendingMachineRepository;


class VendingMachineApplicationService
{

  /** @var IVendingMachineRepository */
  private $repository;

  /** @var  VendingMachine*/
  private $vendingMachine;

  /**
   * @param IVendingMachineRepository $repository
   */
  public function __construct(IVendingMachineRepository $repository)
  {
    $this->repository = $repository;
    $this->vendingMachine = $this->repository->load();
  }



}