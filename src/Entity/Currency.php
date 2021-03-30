<?php

declare(strict_types=1);


namespace MatiCore\Currency;


use Baraja\Doctrine\UUID\UuidIdentifier;
use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;

/**
 * Class Currency
 * @package MatiCore\Currency
 * @ORM\Entity()
 * @ORM\Table(name="app__currency")
 */
class Currency implements ICurrency
{
	public const RATE_MODIFY_OPERATOR_DISABLED = null;
	public const RATE_MODIFY_OPERATOR_MULTIPLY = '*';
	public const RATE_MODIFY_OPERATOR_PLUS = '+';
	public const RATE_MODIFY_OPERATOR_MINUS = '-';

	use SmartObject;
	use UuidIdentifier;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	private string $name;

	/**
	 * @var string
	 * @ORM\Column(type="string", unique=true)
	 */
	private string $code;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	private string $symbol;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	private string $defaultSchema = '%NUM%&nbsp;%SYMBOL%';

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	private string $thousandSeparator = ' ';

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	private string $decimalSeparator = ',';

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private int $decimalPrecision = 2;

	/**
	 * @var float
	 * @ORM\Column(type="float")
	 */
	private float $rate = 1.0;

	/**
	 * @var float
	 * @ORM\Column(type="float")
	 */
	private float $realRate = 1.0;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private bool $rateLock = false;

	/**
	 * @var string|null
	 * @ORM\Column(type="string", nullable=true)
	 */
	private string|null $rateModifyOperator;

	/**
	 * @var float
	 * @ORM\Column(type="float")
	 */
	private float $rateModifyValue = 0.0;

	/**
	 * @var \DateTime|null
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private \DateTime|null $lastUpdate;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private bool $active = false;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean", name="is_default")
	 */
	private bool $default = false;

	/**
	 * Currency constructor.
	 * @param string $name
	 * @param string $code
	 * @param string $symbol
	 */
	public function __construct(string $name, string $code, string $symbol)
	{
		$this->name = $name;
		$this->code = $code;
		$this->symbol = $symbol;
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
	 * @return \DateTime|null
	 */
	public function getLastUpdate(): ?\DateTime
	{
		return $this->lastUpdate;
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
		if ($this->getRateModifyOperator() === null || $this->getRateModifyValue() === 0) {
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