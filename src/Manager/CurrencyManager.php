<?php

declare(strict_types=1);


namespace MatiCore\Currency;

use Baraja\Doctrine\EntityManager;
use Baraja\Doctrine\EntityManagerException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;

/**
 * Class CurrencyManager
 * @package MatiCore\Currency
 */
class CurrencyManager
{

	public const API_URL = 'http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt';

	/**
	 * @var EntityManager
	 */
	private EntityManager $entityManager;

	/**
	 * CurrencyManager constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @param string $id
	 * @return Currency
	 * @throws NoResultException
	 * @throws NonUniqueResultException
	 */
	public function getCurrencyById(string $id): Currency
	{
		return $this->entityManager->getRepository(Currency::class)
			->createQueryBuilder('currency')
			->select('currency')
			->where('currency.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getSingleResult();
	}

	/**
	 * @throws CurrencyException
	 * @throws EntityManagerException
	 */
	public function installCurrencies(): void
	{
		if (count($this->getCurrencies()) > 0) {
			CurrencyException::isInstalled();
		}

		$currencyData = [
			['Česká koruna', 'CZK', 1.0, true, '%NUM%&nbsp;%SYMBOL%', 'Kč', ' ', ',', 0, true, true],
			['Euro', 'EUR', 25.0, false, '%SYMBOL%&nbsp;%NUM%', '€', ',', '.', 2, true, false],
			['Americký dolar', 'USD', 22.0, false, '%SYMBOL%&nbsp;%NUM%', '$', ',', '.', 2, false, false],
			['Libra šterlinků', 'GBP', 35.0, false, '%NUM%&nbsp;%SYMBOL%', '£', ',', '.', 2, false, false],
		];

		foreach ($currencyData as $data) {
			$currency = new Currency($data[0], $data[1], $data[5]);
			$currency->setDefaultSchema($data[4]);
			$currency->setRate($data[2]);
			$currency->setRateLock($data[3]);
			$currency->setThousandSeparator($data[6]);
			$currency->setDecimalSeparator($data[7]);
			$currency->setDecimalPrecision($data[8]);
			$currency->setActive($data[9]);
			$currency->setDefault($data[10]);
			$this->entityManager->persist($currency);
		}

		$this->entityManager->flush();
	}

	/**
	 * @return Currency[]
	 */
	public function getCurrencies(): array
	{
		static $cache;

		if ($cache === null) {
			$cache = $this->entityManager->getRepository(Currency::class)
					->createQueryBuilder('currency')
					->select('currency')
					->orderBy('currency.name', 'ASC')
					->getQuery()
					->getResult() ?? [];
		}

		return $cache;
	}

	/**
	 * @return Currency[]
	 */
	public function getActiveCurrencies(): array
	{
		static $cache;

		if ($cache === null) {
			$cache = $this->entityManager->getRepository(Currency::class)
					->createQueryBuilder('currency')
					->select('currency')
					->where('currency.active = :t')
					->setParameter('t', 1)
					->orderBy('currency.name', 'ASC')
					->getQuery()
					->getResult() ?? [];
		}

		return $cache;
	}

	/**
	 * @throws \Exception
	 */
	public function updateCurrency(): void
	{
		$data = Strings::normalize(file_get_contents(self::API_URL));

		foreach (explode("\n", $data) as $line) {
			if ((bool) preg_match('/^\d$/', $line[0]) === false && Strings::upper($line[0]) === $line[0]) {
				[$country, $currency, $quantity, $isoCode, $rate] = explode('|', $line);
				try {
					$entity = $this->getCurrencyByIsoCode($isoCode);
					$currencyRate = (float) ((float) str_replace(',', '.', $rate) / (int) $quantity);

					$entity->setRealRate($currencyRate);
					$entity->setModifiedRate();

					$entity->setLastUpdate(DateTime::from('NOW'));

					$this->entityManager->getUnitOfWork()->commit($entity);
				} catch (NoResultException|NonUniqueResultException $e) {

				}
			}
		}
	}

	/**
	 * @param string $code
	 * @return Currency
	 * @throws NoResultException
	 * @throws NonUniqueResultException
	 */
	public function getCurrencyByIsoCode(string $code): Currency
	{
		return $this->entityManager->getRepository(Currency::class)
			->createQueryBuilder('currency')
			->select('currency')
			->where('currency.code = :code')
			->setParameter('code', $code)
			->getQuery()
			->getSingleResult();
	}

	/**
	 * @param Currency $currency
	 * @param \DateTime $dateTime
	 * @return CurrencyTemp
	 * @throws \Exception
	 */
	public function getCurrencyRateByDate(Currency $currency, \DateTime $dateTime): CurrencyTemp
	{
		$data = Strings::normalize(file_get_contents(self::API_URL . '?date=' . $dateTime->format('d.m.Y')));
		$entity = new CurrencyTemp($currency);
		$entity->setLastUpdate(DateTime::from('NOW'));

		foreach (explode("\n", $data) as $line) {
			if ((bool) preg_match('/^\d$/', $line[0]) === false && Strings::upper($line[0]) === $line[0]) {
				[$country, $currencyName, $quantity, $isoCode, $rate] = explode('|', $line);
				if ($isoCode === $currency->getCode()) {
					$currencyRate = (float) ((float) str_replace(',', '.', $rate) / (int) $quantity);

					$entity->setRealRate($currencyRate);
					$entity->setModifiedRate();
				}
			} elseif (preg_match('/^(\d{2}\.\d{2}\.\d{4})/', $line, $match) && isset($match[1])) {
				$date = DateTime::from($match[1]);
				$entity->setLastUpdate($date);
			}
		}

		return $entity;
	}

	/**
	 * @return Currency
	 * @throws CurrencyException
	 */
	public function getDefaultCurrency(): Currency
	{
		static $cache;

		if ($cache === null) {
			try {
				$cache = $this->entityManager->getRepository(Currency::class)
					->createQueryBuilder('currency')
					->select('currency')
					->where('currency.default = true')
					->getQuery()
					->getSingleResult();
			} catch (NonUniqueResultException $e) {
				CurrencyException::tooManyDefaults();
			} catch (NoResultException $e) {
				CurrencyException::missingDefault();
			}
		}

		return $cache;
	}

	/**
	 * @return array
	 */
	public function getCurrenciesForForm(): array
	{
		$list = [];

		foreach ($this->getCurrencies() as $currency) {
			$list[$currency->getId()] = $currency->getCode() . ' - ' . $currency->getName();
		}

		return $list;
	}

}