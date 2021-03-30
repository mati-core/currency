<?php

declare(strict_types=1);


namespace MatiCore\Currency;


use Nette\SmartObject;
use Nette\Utils\DateTime;

/**
 * Class CurrencyTemp
 * @package MatiCore\Currency
 */
class CurrencyTemp implements ICurrency
{
	public const RATE_MODIFY_OPERATOR_DISABLED = null;
	public const RATE_MODIFY_OPERATOR_MULTIPLY = '*';
	public const RATE_MODIFY_OPERATOR_PLUS = '+';
	public const RATE_MODIFY_OPERATOR_MINUS = '-';

	use SmartObject;

	/**
	 * @var string
	 */
	private string $name;

	/**
	 * @var string
	 */
	private string $code;

	/**
	 * @var string
	 */
	private string $symbol;

	/**
	 * @var string
	 */
	private string $defaultSchema;

	/**
	 * @var string
	 */
	private string $thousandSeparator;

	/**
	 * @var string
	 */
	private string $decimalSeparator;

	/**
	 * @var int
	 */
	private int $decimalPrecision;

	/**
	 * @var float
	 */
	private float $rate;

	/**
	 * @var float
	 */
	private float $realRate;

	/**
	 * @var bool
	 */
	private bool $rateLock;

	/**
	 * @var string|null
	 */
	private string|null $rateModifyOperator;

	/**
	 * @var float
	 */
	private float $rateModifyValue;

	/**
	 * @var \DateTime|null
	 */
	private \DateTime|null $lastUpdate;

	/**
	 * @var bool
	 */
	private bool $active;

	/**
	 * @var bool
	 */
	private bool $default;

	/**
	 * CurrencyTemp constructor.
	 * @param Currency $currency
	 */
	public function __construct(Currency $currency)
	{
		$this->name = $currency->getName();
		$this->code = $currency->getCode();
		$this->symbol = $currency->getSymbol();
		$this->defaultSchema = $currency->getDefaultSchema();
		$this->thousandSeparator = $currency->getThousandSeparator();
		$this->decimalSeparator = $currency->getDecimalSeparator();
		$this->decimalPrecision = $currency->getDecimalPrecision();
		$this->rate = $currency->getRate();
		$this->realRate = $currency->getRealRate();
		$this->rateLock = $currency->isRateLock();
		$this->rateModifyOperator = $currency->getRateModifyOperator();
		$this->rateModifyValue = $currency->getRateModifyValue();
		$this->lastUpdate = $currency->getLastUpdate();
		$this->active = $currency->isActive();
		$this->default = $currency->isDefault();
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name): void
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 * @param string $code
	 */
	public function setCode(string $code): void
	{
		$this->code = $code;
	}

	/**
	 * @return string
	 */
	public function getSymbol(): string
	{
		return $this->symbol;
	}

	/**
	 * @param string $symbol
	 */
	public function setSymbol(string $symbol): void
	{
		$this->symbol = $symbol;
	}

	/**
	 * @return string
	 */
	public function getDefaultSchema(): string
	{
		return $this->defaultSchema;
	}

	/**
	 * @param string $defaultSchema
	 */
	public function setDefaultSchema(string $defaultSchema): void
	{
		$this->defaultSchema = $defaultSchema;
	}

	/**
	 * @return string
	 */
	public function getThousandSeparator(): string
	{
		return $this->thousandSeparator;
	}

	/**
	 * @param string $thousandSeparator
	 */
	public function setThousandSeparator(string $thousandSeparator): void
	{
		$this->thousandSeparator = $thousandSeparator;
	}

	/**
	 * @return string
	 */
	public function getDecimalSeparator(): string
	{
		return $this->decimalSeparator;
	}

	/**
	 * @param string $decimalSeparator
	 */
	public function setDecimalSeparator(string $decimalSeparator): void
	{
		$this->decimalSeparator = $decimalSeparator;
	}

	/**
	 * @return int
	 */
	public function getDecimalPrecision(): int
	{
		return $this->decimalPrecision;
	}

	/**
	 * @param int $decimalPrecision
	 */
	public function setDecimalPrecision(int $decimalPrecision): void
	{
		$this->decimalPrecision = $decimalPrecision;
	}

	/**
	 * @return string
	 */
	public function getRateFormatted(): string
	{
		return Number::format($this->getRate(), 3, '.');
	}

	/**
	 * @return float
	 */
	public function getRate(): float
	{
		return (float) $this->rate;
	}

	/**
	 * @param float $rate
	 */
	public function setRate(float $rate): void
	{
		$this->rate = $rate;
	}

	/**
	 * @return string
	 */
	public function getRealRateFormatted(): string
	{
		return Number::format($this->getRealRate(), 3, '.');
	}

	/**
	 * @return float
	 */
	public function getRealRate(): float
	{
		return (float) $this->realRate;
	}

	/**
	 * @param float $realRate
	 */
	public function setRealRate(float $realRate): void
	{
		$this->realRate = $realRate;
	}

	/**
	 * @return bool
	 */
	public function isRateLock(): bool
	{
		return $this->rateLock;
	}

	/**
	 * @return bool
	 */
	public function isRateModified(): bool
	{
		return $this->getRealRate() !== $this->getRate();
	}

	/**
	 * @param bool $rateLock
	 */
	public function setRateLock(bool $rateLock): void
	{
		$this->rateLock = $rateLock;
	}

	/**
	 * @return \DateTime
	 * @throws \Exception
	 */
	public function getLastUpdate(): \DateTime
	{
		return $this->lastUpdate ?? DateTime::from('NOW');
	}

	/**
	 * @param \DateTime|null $lastUpdate
	 */
	public function setLastUpdate(?\DateTime $lastUpdate): void
	{
		$this->lastUpdate = $lastUpdate;
	}

	public function setModifiedRate(): void
	{
		if($this->isRateLock() === false){
			$this->rate = $this->getModifiedRate($this->getRealRate());
		}
	}

	/**
	 * @param float $realRate
	 * @return float
	 */
	public function getModifiedRate(float $realRate): float
	{
		if ($this->getRateModifyOperator() === null || $this->getRateModifyValue() === 0.0) {
			return $realRate;
		}

		$rate = $realRate;

		switch ($this->getRateModifyOperator()) {
			case self::RATE_MODIFY_OPERATOR_MULTIPLY:
				$rate *= $this->getRateModifyValue();
				break;
			case self::RATE_MODIFY_OPERATOR_PLUS:
				$rate += $this->getRateModifyValue();
				break;
			case self::RATE_MODIFY_OPERATOR_MINUS:
				$rate -= $this->getRateModifyValue();
				break;
		}

		return $rate;
	}

	/**
	 * @return string|null
	 */
	public function getRateModifyOperator(): ?string
	{
		return $this->rateModifyOperator;
	}

	/**
	 * @param string|null $rateModifyOperator
	 */
	public function setRateModifyOperator(?string $rateModifyOperator): void
	{
		$this->rateModifyOperator = $rateModifyOperator;
	}

	/**
	 * @return float
	 */
	public function getRateModifyValue(): float
	{
		return $this->rateModifyValue;
	}

	/**
	 * @param float $rateModifyValue
	 */
	public function setRateModifyValue(float $rateModifyValue): void
	{
		$this->rateModifyValue = $rateModifyValue;
	}

	/**
	 * @return bool
	 */
	public function isActive(): bool
	{
		return $this->active;
	}

	/**
	 * @param bool $active
	 */
	public function setActive(bool $active): void
	{
		$this->active = $active;
	}

	/**
	 * @return bool
	 */
	public function isDefault(): bool
	{
		return $this->default;
	}

	/**
	 * @param bool $default
	 */
	public function setDefault(bool $default): void
	{
		$this->default = $default;
	}

	/**
	 * @return string
	 */
	public function getFormatExample(): string
	{
		return Number::formatPrice(1234567.89, $this);
	}

}