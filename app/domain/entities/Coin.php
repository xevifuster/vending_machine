<?php

namespace app\domain\entities;


enum Coin : int
{
  case FIVE = 5;
  case TEN = 10;
  case TWENTY_FIVE = 25;
  case ONE_EURO = 100;

  /**
   * @param float $value
   * @return self
   */
  public static function coinCentsIntValue(float $value): self
  {
    return match ($value) {
      0.05 => self::FIVE,
      0.10 => self::TEN,
      0.25 => self::TWENTY_FIVE,
      1.00 => self::ONE_EURO,
      default => throw new \InvalidArgumentException("Invalid coin"),
    };
  }

}
