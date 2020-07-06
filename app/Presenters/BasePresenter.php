<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Database\Connection;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Presenter
{
	protected Connection $database;

	public function __construct(Connection $database)
	{
		$this->database = $database;
	}
}
