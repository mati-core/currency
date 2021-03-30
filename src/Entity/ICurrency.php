<?php

declare(strict_types=1);

namespace MatiCore\Currency;

/**
 * Interface ICurrency
 * @package MatiCore\Currency
 */
interface ICurrency
{

	/**
	 * @return int
	 */
	public function getDecimalPrecision(): int;

	/**
	 * @return string
	 */
	public function getDecimalSeparator(): string;

	/**
	 * @return string
	 */
	public function getThousandSeparator(): string;

	/**
	 * @return string
	 */
	public function getDefaultSchema(): string;

	/**
	 * @return string
	 */
	public function getSymbol(): string;

}