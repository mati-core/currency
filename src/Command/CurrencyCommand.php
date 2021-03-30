<?php

declare(strict_types=1);

namespace MatiCore\Currency;


use MatiCore\Constant\ConstantManager;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tracy\Debugger;
use Tracy\Dumper;

/**
 * Class CurrencyCommand
 * @package MatiCore\Currency
 */
class CurrencyCommand extends Command
{

	/**
	 * @var CurrencyManager
	 */
	private CurrencyManager $currencyManager;

	/**
	 * @var Storage
	 */
	private Storage $cache;

	/**
	 * @var ConstantManager
	 */
	private ConstantManager $constant;

	/**
	 * @var SymfonyStyle|null
	 */
	private SymfonyStyle|null $io;

	/**
	 * CurrencyCommand constructor.
	 * @param CurrencyManager $currencyManager
	 * @param Storage $cache
	 * @param ConstantManager $constant
	 */
	public function __construct(CurrencyManager $currencyManager, Storage $cache, ConstantManager $constant)
	{
		parent::__construct();
		$this->currencyManager = $currencyManager;
		$this->cache = $cache;
		$this->constant = $constant;
	}

	protected function configure(): void
	{
		$this->setName('app:currency:update')->setDescription('Update currencies.');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		try {
			$this->io = new SymfonyStyle($input, $output);

			$output->writeln('==============================================');
			$output->writeln('           AKTUALIZACE KURZU MEN              ');
			$output->writeln('');
			$output->writeln('');

			$output->writeln('loading CSOB...');

			$dataCsob = $this->loadCsobData();

			$output->writeln('Done');

			$output->writeln('loading CNB...');

			$dataCnb = $this->loadCnbData();

			$output->writeln('Done');

			$this->constant->set('csob_kurz', Json::encode($dataCsob));

			$this->constant->set('cnb_kurz', Json::encode($dataCnb));

			$this->cache->clean([Cache::TAGS => 'currencyNav']);

			$this->currencyManager->updateCurrency();

			$this->io->writeln('Currencies was updated.');


			$output->writeln('');
			$output->writeln('');
			$output->writeln('                   Finished                   ');
			$output->writeln('==============================================');
			$output->writeln('');
			$output->writeln('');

			return 0;
		} catch (\Throwable $e) {
			Debugger::log($e);
			$output->writeln('<error>' . $e->getMessage() . '</error>');

			return 1;
		}
	}

	/**
	 * @return float[][]
	 * @throws \Exception
	 */
	private function loadCsobData(): array
	{
		$date = DateTime::from('NOW');

		echo Dumper::toTerminal($date->format('Y-m-d'));

		$link = 'https://www.csob.cz/portal/lide/kurzovni-listek/-/date/' . $date->format('Y-m-d') . '/kurzovni-listek.xml';

		$this->io->writeln($link);

		$curl = curl_init($link);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)');
		$xmlData = curl_exec($curl);
		curl_close($curl);

		//$xmlData = file_get_contents($link);

		$data = [
			'buy' => [],
			'sell' => [],
		];

		if (!$xmlData) {
			return $data;
		}

		$xml = simplexml_load_string($xmlData);

		foreach ($xml->Country as $country) {
			if ((string) $country['ID'] === 'EUR') {
				$data['buy'][] = (float) $country->FXcashless['Buy'];
				$data['sell'][] = (float) $country->FXcashless['Sale'];
			}
		}

		$date->modify('-1 day');

		echo Dumper::toTerminal($date->format('Y-m-d'));

		$link = 'https://www.csob.cz/portal/lide/kurzovni-listek/-/date/' . $date->format('Y-m-d') . '/kurzovni-listek.xml';

		$this->io->writeln($link);

		$curl = curl_init($link);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)');
		$xmlData = curl_exec($curl);
		curl_close($curl);

		//$xmlData = file_get_contents($link);

		if (!$xmlData) {
			return [
				'buy' => [],
				'sell' => [],
			];
		}

		$xml = simplexml_load_string($xmlData);

		foreach ($xml->Country as $country) {
			if ((string) $country['ID'] === 'EUR') {
				$data['buy'][] = (float) $country->FXcashless['Buy'];
				$data['sell'][] = (float) $country->FXcashless['Sale'];
			}
		}

		echo Dumper::toTerminal($data);

		return $data;
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	private function loadCnbData(): array
	{
		$data = [];

		$date = DateTime::from('NOW');

		if ((int) $date->format('H') < 14 || ((int) $date->format('H') < 14 && (int) $date->format('m') < 30)) {
			$date->modify('-1 day');
		}

		echo Dumper::toTerminal($date->format('Y-m-d'));

		$txt = Strings::normalize(file_get_contents('http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt'));

		foreach (explode("\n", $txt) as $line) {
			if ((bool) preg_match('/^\d$/', $line[0]) === false && Strings::upper($line[0]) === $line[0]) {
				[$country, $currency, $quantity, $isoCode, $rate] = explode('|', $line);

				if ($isoCode === 'EUR') {
					$data[] = (float) str_replace(',', '.', $rate);
				}
			}
		}

		$date->modify('-1 day');

		echo Dumper::toTerminal($date->format('Y-m-d'));

		$txt = Strings::normalize(file_get_contents('http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt?date=' . $date->format('d.m.Y')));

		foreach (explode("\n", $txt) as $line) {
			if ((bool) preg_match('/^\d$/', $line[0]) === false && Strings::upper($line[0]) === $line[0]) {
				[$country, $currency, $quantity, $isoCode, $rate] = explode('|', $line);

				if ($isoCode === 'EUR') {
					$data[] = (float) str_replace(',', '.', $rate);
				}
			}
		}

		echo Dumper::toTerminal($data);

		return $data;
	}

}