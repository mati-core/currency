<?php

declare(strict_types=1);

namespace MatiCore\Currency;


/**
 * Interface CurrencyManagerAccessor
 * @package MatiCore\Currency
 */
interface CurrencyManagerAccessor
{

	/**
	 * @return CurrencyManager
	 */
	public function get(): CurrencyManager;

}