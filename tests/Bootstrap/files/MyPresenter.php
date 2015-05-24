<?php

abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	private $attr;

	function getAttr()
	{
		return $this->attr;
	}

	function setAttr($attr)
	{
		$this->attr = $attr;
	}
}

class Presenter1 extends BasePresenter
{
	
}