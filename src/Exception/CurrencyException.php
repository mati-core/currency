<?php

declare(strict_types=1);


namespace MatiCore\Currency;

/**
 * Class CurrencyException
 * @package MatiCore\Currency
 */
class CurrencyException extends \Exception
{

	/**
	 * @throws CurrencyException
	 */
	public static function isInstalled(): void
	{
		throw new self('Měny jsou již nainstalovány, nebo tabulka s měnami není prázdná.');
	}

	/**
	 * @throws CurrencyException
	 */
	public static function tooManyDefaults(): void
	{
		throw new self('Více měn je nastaveno jako výchozích!');
	}

	/**
	 * @throws CurrencyException
	 */
	public static function missingDefault(): void
	{
		throw new self('Není nastavena výchozí měna!');
	}

}