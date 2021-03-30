<?php

declare(strict_types=1);

namespace MatiCore\Currency\Filter;

use MatiCore\Currency\Currency;
use MatiCore\Currency\CurrencyException;
use MatiCore\Currency\CurrencyManagerAccessor;
use MatiCore\Currency\Number;

/**
 * Class CurrencyLatteFilter
 * @package MatiCore\Currency\Filter
 */
class CurrencyLatteFilter
{

	/**
	 * @var CurrencyManagerAccessor
	 */
	private CurrencyManagerAccessor $currencyManager;

	/**
	 * CurrencyLatteFilter constructor.
	 * @param CurrencyManagerAccessor $currencyManager
	 */
	public function __construct(CurrencyManagerAccessor $currencyManager)
	{
		$this->currencyManager = $currencyManager;
	}

	/**
	 * @param float|int|null $haystack
	 * @param Currency|null $currency
	 * @return string
	 * @throws CurrencyException
	 */
	public function __invoke(float|int|null $haystack, ?Currency $currency = null): string
	{
		if($currency === null){
			$currency = $this->currencyManager->get()->getDefaultCurrency();
		}

		return Number::formatPrice((float) $haystack, $currency);
	}

}