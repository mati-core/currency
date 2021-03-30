<?php

declare(strict_types=1);


namespace App\AdminModule\Presenters;


use Baraja\Doctrine\EntityManagerException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use MatiCore\Currency\Currency;
use MatiCore\Currency\CurrencyException;
use MatiCore\Currency\CurrencyManagerTrait;
use MatiCore\Form\FormFactoryTrait;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Tracy\Debugger;

/**
 * Class CurrencyInnerPackagePresenter
 * @package App\AdminModule\Presenters
 */
class CurrencyInnerPackagePresenter extends BaseAdminPresenter
{

	use FormFactoryTrait;
	use CurrencyManagerTrait;

	/**
	 * @var Currency|null
	 */
	private Currency|null $editedCurrency;

	public function actionDefault(): void
	{
		$this->template->currencies = $this->currencyManager->get()->getCurrencies();
	}

	/**
	 * @param string $id
	 * @throws AbortException
	 */
	public function actionDetail(string $id): void
	{
		try {
			$this->editedCurrency = $this->currencyManager->get()->getCurrencyById($id);
			$this->template->currency = $this->editedCurrency;
		} catch (NonUniqueResultException|NoResultException $e) {
			$this->flashMessage('Požadovaná měna nebyla nalezena.', 'error');

			$this->redirect('default');
		}
	}

	/**
	 * @throws AbortException
	 */
	public function handleInstall(): void
	{
		try {
			$this->currencyManager->get()->installCurrencies();
		} catch (CurrencyException $e) {
			$this->flashMessage($e->getMessage(), 'error');
		} catch (EntityManagerException $e) {
			$this->flashMessage('Chyba při ukládání do databáze.', 'error');
		}

		$this->redirect('default');
	}

	/**
	 * @param string $id
	 * @throws AbortException
	 */
	public function handleDelete(string $id): void
	{
		try {
			$currency = $this->currencyManager->get()->getCurrencyById($id);

			$this->entityManager->remove($currency)->flush();
			$this->flashMessage('Měna byla úspěšně odebrána.', 'success');
		} catch (NonUniqueResultException|NoResultException $e) {
			$this->flashMessage('Požadovaná měna neexistuje.', 'error');
		} catch (EntityManagerException $e) {
			$this->flashMessage('Měnu nezle odebrat, protože je využívána.', 'error');
		}

		$this->redirect('default');
	}

	/**
	 * @param string $id
	 * @throws AbortException
	 */
	public function handleLock(string $id): void
	{
		try {
			$currency = $this->currencyManager->get()->getCurrencyById($id);
			$currency->setRateLock(!$currency->isRateLock());

			$this->entityManager->flush($currency);
		} catch (NoResultException|NonUniqueResultException $e) {
			$this->flashMessage('Požadovaná měna neexistuje.', 'error');
		} catch (EntityManagerException $e) {
			Debugger::log($e);
			$this->flashMessage('Chyba při ukládání do databáze.', 'error');
		}

		$this->redirect('default');
	}

	public function handleUpdate(): void
	{
		try {
			$this->currencyManager->get()->updateCurrency();

			$this->flashMessage('Kurz ČNB byl úspěšně aktualizován.', 'success');
		} catch (EntityManagerException $e) {
			Debugger::log($e);
			$this->flashMessage('Chyba při ukládání do databáze.', 'error');
		}

		$this->redirect('default');
	}

	/**
	 * @param string $id
	 */
	public function handleActive(string $id): void
	{
		try {
			$currency = $this->currencyManager->get()->getCurrencyById($id);
			$currency->setActive(!$currency->isActive());

			$this->entityManager->flush($currency);
		} catch (NoResultException|NonUniqueResultException $e) {
			$this->flashMessage('Požadovaná měna neexistuje.', 'error');
		} catch (EntityManagerException $e) {
			Debugger::log($e);
			$this->flashMessage('Chyba při ukládání do databáze.', 'error');
		}
	}

	/**
	 * @return Form
	 */
	public function createComponentCreateForm(): Form
	{
		$form = $this->formFactory->create();

		$form->addText('name', 'Název')
			->setRequired('Zadejte název měny');

		$form->addText('code', 'Iso kód')
			->setRequired('Zadejte ISO kód měny (např. CZK)');

		$form->addText('symbol', 'Symbol')
			->setRequired('Zadejte symbol měny');

		$form->addText('thousandSeparator', 'Oddělovat tisíců')
			->setDefaultValue('&nbsp;')
			->setRequired('Zadejte oddělovat tisíců');

		$form->addText('decimalSeparator', 'Desetinný oddělovat')
			->setDefaultValue(',')
			->setRequired('Zadejte desetinný oddělovač.');

		$form->addInteger('decimalPrecision', 'Počet desetinných míst')
			->setDefaultValue(2)
			->setRequired('Zadejte počet desetinných míst.');

		$form->addText('schema', 'Schéma')
			->setDefaultValue('%NUM%&nbsp;%SYMBOL%')
			->setRequired('Zadejte schéma pro zobrazení měny.');

		$form->addText('rate', 'Kurz')
			->setDefaultValue(1.0);

		$operators = [
			Currency::RATE_MODIFY_OPERATOR_DISABLED => 'Vypnuto',
			Currency::RATE_MODIFY_OPERATOR_MULTIPLY => Currency::RATE_MODIFY_OPERATOR_MULTIPLY,
			Currency::RATE_MODIFY_OPERATOR_PLUS => Currency::RATE_MODIFY_OPERATOR_PLUS,
			Currency::RATE_MODIFY_OPERATOR_MINUS => Currency::RATE_MODIFY_OPERATOR_MINUS,
		];

		$form->addSelect('modifyOperator', 'Automatická úprava kurzu', $operators)
			->setDefaultValue(null);

		$form->addText('modifyValue', 'Hodnota modifikace')
			->setDefaultValue(0)
			->setRequired('Zadejte hodnotu modifikace');

		$form->addSubmit('submit', 'Create');

		/**s
		 * @param Form $form
		 * @param ArrayHash $values
		 */
		$form->onSuccess[] = function (Form $form, ArrayHash $values): void {
			try {
				$currency = new Currency($values->name, $values->code, $values->symbol);
				$currency->setThousandSeparator($values->thousandSeparator);
				$currency->setDecimalSeparator($values->decimalSeparator);
				$currency->setDecimalPrecision((int) $values->decimalPrecision);
				$currency->setDefaultSchema($values->schema);
				$currency->setRate((float) str_replace(',', '.', $values->rate));

				$currency->setRateModifyOperator($values->modifyOperator);
				$currency->setRateModifyValue((float) str_replace(',', '.', $values->modifyValue));
				$currency->setModifiedRate();

				$this->entityManager->persist($currency)->flush($currency);

				$this->flashMessage('Měna ' . $currency->getName() . ' byla úspěšně vytvořena.', 'success');
			} catch (EntityManagerException $e) {
				Debugger::log($e);
				$this->flashMessage('Chyba při ukládání do databáze.', 'error');
			}

			$this->redirect('default');
		};

		return $form;
	}

	/**
	 * @return Form
	 * @throws CurrencyException
	 */
	public function createComponentEditForm(): Form
	{
		if ($this->editedCurrency === null) {
			throw new CurrencyException('Edited currency is null');
		}

		$form = $this->formFactory->create();

		$form->addText('name', 'Název')
			->setDefaultValue($this->editedCurrency->getName())
			->setRequired('Zadejte název měny');

		$form->addText('code', 'Iso kód')
			->setDefaultValue($this->editedCurrency->getCode())
			->setRequired('Zadejte ISO kód měny (např. CZK)');

		$form->addText('symbol', 'Symbol')
			->setDefaultValue($this->editedCurrency->getSymbol())
			->setRequired('Zadejte symbol měny');

		$form->addText('thousandSeparator', 'Oddělovat tisíců')
			->setDefaultValue($this->editedCurrency->getThousandSeparator())
			->setRequired('Zadejte oddělovat tisíců');

		$form->addText('decimalSeparator', 'Desetinný oddělovat')
			->setDefaultValue($this->editedCurrency->getDecimalSeparator())
			->setRequired('Zadejte desetinný oddělovač.');

		$form->addInteger('decimalPrecision', 'Počet desetinných míst')
			->setDefaultValue($this->editedCurrency->getDecimalPrecision())
			->setRequired('Zadejte počet desetinných míst.');

		$form->addText('schema', 'Schéma')
			->setDefaultValue($this->editedCurrency->getDefaultSchema())
			->setRequired('Zadejte schéma pro zobrazení měny.');

		$form->addText('rate', 'Kurz')
			->setDefaultValue($this->editedCurrency->getRate());

		$operators = [
			Currency::RATE_MODIFY_OPERATOR_DISABLED => 'Vypnuto',
			Currency::RATE_MODIFY_OPERATOR_MULTIPLY => Currency::RATE_MODIFY_OPERATOR_MULTIPLY,
			Currency::RATE_MODIFY_OPERATOR_PLUS => Currency::RATE_MODIFY_OPERATOR_PLUS,
			Currency::RATE_MODIFY_OPERATOR_MINUS => Currency::RATE_MODIFY_OPERATOR_MINUS,
		];

		$form->addSelect('modifyOperator', 'Automatická úprava kurzu', $operators)
			->setDefaultValue($this->editedCurrency->getRateModifyOperator());

		$form->addText('modifyValue', 'Hodnota modifikace')
			->setDefaultValue($this->editedCurrency->getRateModifyValue())
			->setRequired('Zadejte hodnotu modifikace');

		$form->addSubmit('submit', 'Save');

		/**s
		 * @param Form $form
		 * @param ArrayHash $values
		 */
		$form->onSuccess[] = function (Form $form, ArrayHash $values): void {
			try {
				$this->editedCurrency->setName($values->name);
				$this->editedCurrency->setCode($values->code);
				$this->editedCurrency->setSymbol($values->symbol);
				$this->editedCurrency->setThousandSeparator($values->thousandSeparator);
				$this->editedCurrency->setDecimalSeparator($values->decimalSeparator);
				$this->editedCurrency->setDecimalPrecision((int) $values->decimalPrecision);
				$this->editedCurrency->setDefaultSchema($values->schema);
				$this->editedCurrency->setRate((float) str_replace(',', '.', $values->rate));

				$this->editedCurrency->setRateModifyOperator($values->modifyOperator);
				$this->editedCurrency->setRateModifyValue((float) str_replace(',', '.', $values->modifyValue));
				$this->editedCurrency->setModifiedRate();

				$this->entityManager->flush($this->editedCurrency);

				$this->flashMessage('Změny byly úspěšně uloženy', 'success');
			} catch (EntityManagerException $e) {
				Debugger::log($e);
				$this->flashMessage('Chyba při ukládání do databáze.', 'error');
			}

			$this->redirect('default');
		};

		return $form;
	}

}