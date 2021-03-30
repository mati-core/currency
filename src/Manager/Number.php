<?php

declare(strict_types=1);


namespace MatiCore\Currency;

/**
 * Class Number
 * @package MatiCore\Currency
 */
class Number
{

	/**
	 * @param float $value
	 * @param ICurrency $currency
	 * @param int|null $decimalPrecision
	 * @param string|null $schema
	 * @param string|null $thousandSeparator
	 * @param string|null $decimalSeparator
	 * @param string|null $symbol
	 * @return string
	 */
	public static function formatPrice(
		float $value,
		ICurrency $currency,
		?int $decimalPrecision = null,
		?string $schema = null,
		?string $thousandSeparator = null,
		?string $decimalSeparator = null,
		?string $symbol = null
	): string
	{
		$decimalPrecision = $decimalPrecision ?? $currency->getDecimalPrecision();
		$decimalSeparator = $decimalSeparator ?? $currency->getDecimalSeparator();
		$thousandSeparator = $thousandSeparator ?? $currency->getThousandSeparator();
		$schema = $schema ?? $currency->getDefaultSchema();
		$symbol = $symbol ?? $currency->getSymbol();

		if($value < 0){
			$value = -$value;
			$value = round($value, $decimalPrecision);
			$value = -$value;
		}

		$price = number_format($value, $decimalPrecision, $decimalSeparator, $thousandSeparator);

		return str_replace(['%NUM%', '%SYMBOL%'], [$price, $symbol], $schema);
	}

	/**
	 * @param float $value
	 * @param int $decimalPrecision
	 * @param string|null $decimalSeparator
	 * @param string|null $thousandSeparator
	 * @return string
	 */
	public static function format(
		float $value,
		int $decimalPrecision,
		?string $decimalSeparator = ',',
		? string $thousandSeparator = ' '
	): string
	{
		return number_format($value, $decimalPrecision, $decimalSeparator, $thousandSeparator);
	}

}