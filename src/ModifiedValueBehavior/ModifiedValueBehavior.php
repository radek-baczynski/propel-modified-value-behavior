<?php
use Radekb\ModifiedValueBehavior\ModifiedValueModelBuilderModifier;

class ModifiedValueBehavior extends Behavior
{
	/** @var  ModifiedValueModelBuilderModifier */
	private $modelBuilderModifier;

	/**
	 * @return ModifiedValueModelBuilderModifier
	 */
	public function getObjectBuilderModifier()
	{
		if (null === $this->modelBuilderModifier)
		{
			$this->modelBuilderModifier = new ModifiedValueModelBuilderModifier($this);
		}

		return $this->modelBuilderModifier;
	}
}