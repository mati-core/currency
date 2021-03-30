<?php

declare(strict_types=1);

namespace MatiCore\Currency;


/**
 * Trait CurrencyManagerTrait
 * @package MatiCore\Currency
 */
trait CurrencyManagerTrait
{

	/**
	 * @var CurrencyManagerAccessor
	 * @inject
	 */
	public CurrencyManagerAccessor $currencyManager;

}