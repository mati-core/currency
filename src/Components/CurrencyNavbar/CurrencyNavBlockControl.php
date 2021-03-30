<?php

declare(strict_types=1);

namespace MatiCore\Currency;

use Exception;
use MatiCore\Cms\Nav\NavBlockControl;
use MatiCore\Constant\ConstantManagerAccessor;
use MatiCore\Constant\Exception\ConstantException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tracy\Debugger;

/**
 * Class CurrencyNavBlockControl
 * @package MatiCore\Currency
 */
class CurrencyNavBlockControl extends NavBlockControl
{

	/**
	 * @var string
	 */
	private string $blockName = 'currency';

	/**
	 * @var ConstantManagerAccessor
	 */
	private ConstantManagerAccessor $constant;

	/**
	 * CurrencyNavBlockControl constructor.
	 * @param ConstantManagerAccessor $constant
	 */
	public function __construct(ConstantManagerAccessor $constant)
	{
		$this->constant = $constant;
	}

	/**
	 * @return string
	 */
	public function getBlockName(): string
	{
		return $this->blockName;
	}

	public function render(): void
	{
		$presenter = $this->getPresenter();

		$show = false;
		if ($presenter !== null) {
			$show = $presenter->checkUserRight('page__nav') && $presenter->checkUserRight('page__nav__currency');
		}

		$template = $this->template;
		$template->setFile(__DIR__ . '/default.latte');
		$template->show = $show;
		$template->render();
	}

	/**
	 * @return float[][]
	 * @throws JsonException
	 * @throws ConstantException
	 */
	private function loadCsobData(): array
	{
		$data = $this->constant->get()->get('csob_kurz');

		if ($data === null) {
			return [
				'buy' => [0.0, 0.0],
				'sell' => [0.0, 0.0],
			];
		}

		return Json::decode($data, Json::FORCE_ARRAY);
	}

	/**
	 * @return float[][]
	 * @throws ConstantException
	 * @throws JsonException
	 */
	private function loadCnbData(): array
	{
		$data = $this->constant->get()->get('cnb_kurz');

		if ($data === null) {
			return [
				'buy' => [0.0, 0.0],
				'sell' => [0.0, 0.0],
			];
		}

		return Json::decode($data, Json::FORCE_ARRAY);
	}

	/**
	 * @return string
	 */
	public function getCsobData(): string
	{
		try {
			$data = $this->loadCsobData();

			if (
				!isset($data['buy'][0], $data['buy'][1], $data['sell'][0], $data['sell'][1])
				|| $data['buy'][0] === null
				|| $data['buy'][1] === null
				|| $data['sell'][0] === null
				|| $data['sell'][1] === null
				|| (float) $data['buy'][0] === 0.0
				|| (float) $data['buy'][1] === 0.0
				|| (float) $data['sell'][0] === 0.0
				|| (float) $data['sell'][1] === 0.0
			) {
				return '??? / ???';
			}

			$ret = '';

			//nakup
			if ($data['buy'][0] > $data['buy'][1]) {
				$ret .= '<i class="fas fa-arrow-up text-success fa-fw fa-xs"></i>';
			} elseif ($data['buy'][0] === $data['buy'][1]) {
				$ret .= '<i class="fas fa-arrow-right text-blue fa-fw fa-xs"></i>';
			} else {
				$ret .= '<i class="fas fa-arrow-down text-danger fa-fw fa-xs"></i>';
			}

			$ret .= Number::format((float) $data['buy'][0], 3, '.') . ' / ';

			//prodej
			if ($data['sell'][0] > $data['sell'][1]) {
				$ret .= '<i class="fas fa-arrow-up text-success fa-fw fa-xs"></i>';
			} elseif ($data['sell'][0] === $data['sell'][1]) {
				$ret .= '<i class="fas fa-arrow-right text-blue fa-fw fa-xs"></i>';
			} else {
				$ret .= '<i class="fas fa-arrow-down text-danger fa-fw fa-xs"></i>';
			}

			$ret .= Number::format((float) $data['sell'][0], 3, '.');

			return $ret;
		} catch (Exception $e) {
			Debugger::log($e);
			return '??? / ???';
		}
	}

	/**
	 * @return string
	 */
	public function getCnbData(): string
	{
		try {
			$data = $this->loadCnbData();

			if (
				!isset($data[0], $data[1])
				|| $data[0] === null
				|| $data[1] === null
				|| (float) $data[0] === 0
				|| (float) $data[1] === 0
			) {
				return '??? / ???';
			}

			//nakup
			if ($data[0] > $data[1]) {
				return '<i class="fas fa-arrow-up text-success fa-fw fa-xs"></i>' . Number::format((float) $data[0], 3, '.');
			}

			if ($data[0] === $data[1]) {
				return '<i class="fas fa-arrow-right text-blue fa-fw fa-xs"></i>' . Number::format((float) $data[0], 3, '.');
			}

			return '<i class="fas fa-arrow-down text-danger fa-fw fa-xs"></i>' . Number::format((float) $data[0], 3, '.');
		} catch (Exception $e) {
			Debugger::log($e);
			return '??? / ???';
		}
	}

}