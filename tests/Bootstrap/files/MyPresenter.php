<?php

declare(strict_types=1);

abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	private $attr;


	public function getAttr()
	{
		return $this->attr;
	}


	public function setAttr($attr)
	{
		$this->attr = $attr;
	}
}

class Presenter1 extends BasePresenter
{
}
