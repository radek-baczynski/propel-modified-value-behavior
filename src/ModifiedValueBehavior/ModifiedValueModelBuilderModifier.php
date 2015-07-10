<?php

namespace Radekb\ModifiedValueBehavior;

use Column;

class ModifiedValueModelBuilderModifier
{
	/** @var  \ModifiedValueBehavior */
	protected $behavior;

	/**
	 * @param \ModifiedValueBehavior $behavior
	 */
	function __construct(\ModifiedValueBehavior $behavior)
	{
		$this->behavior = $behavior;
	}

	/**
	 * @param $builder \ObjectBuilder
	 *
	 * @return string
	 */
	public function objectAttributes($builder)
	{
		return $this->behavior->renderTemplate('objectAttributes');
	}

	/**
	 * Modify setters to store previous values, add useful has/get previous value methods for model
	 *
	 * @param $script
	 */
	public function objectFilter(&$script)
	{
		$parser = new \PropelPHPParser($script, true);

		foreach ($this->behavior->getTable()->getColumns() as $column)
		{
			$setterName = 'set' . $column->getPhpName();

			$oldCode    = $parser->findMethod($setterName);

			$newCode = $this->modifySetterBeginning($oldCode, $column, $parser);
			$newCode = $this->modifySetterBeforeReturn($newCode, $column, $parser);

			$parser->replaceMethod($setterName, $newCode);

			$parser->addMethodAfter($setterName, $this->behavior->renderTemplate('hasPrevious', [
				'columnName' => $column->getPhpName(),
				'peerColumn' => $column->getConstantName(),
			]));

			$parser->addMethodAfter($setterName, $this->behavior->renderTemplate('getPrevious', [
				'columnName' => $column->getPhpName(),
				'peerColumn' => $column->getConstantName(),
				'type'       => $column->getPhpType(),
			]));
		}

		$this->modifySaveAfterCommit($parser);
		$this->modifyPostSaveEvent($parser);
		$this->overrideResetModifiedMethod($parser);
		$this->addGetAllPreviousValuesMethod($parser);

		$script = $parser->getCode();
	}

	/**
	 * @param                  $code
	 * @param Column           $column
	 * @param \PropelPHPParser $parser
	 *
	 * @return mixed
	 */
	private function modifySetterBeginning($code, Column $column, \PropelPHPParser $parser)
	{
		$beforeCode = $this->behavior->renderTemplate('beforeCode', [
			'peerColumn' => $column->getConstantName(),
			'columnName' => $column->getPhpName(),
		]);

		$newCode = preg_replace('/^([^\{]*\{)/', "$1\n        " . $beforeCode, $code);

		return $newCode;
	}

	/**
	 * @param                  $code
	 * @param Column           $column
	 * @param \PropelPHPParser $parser
	 *
	 * @return mixed
	 */
	private function modifySetterBeforeReturn($code, Column $column, \PropelPHPParser $parser)
	{
		$afterCode = $this->behavior->renderTemplate('afterCode', [
			'peerColumn' => $column->getConstantName(),
			'columnName' => $column->getPhpName(),
		]);

		$newCode = preg_replace('/(return .+;)/', $afterCode . "\n        $1", $code);

		return $newCode;
	}

	/**
	 * Clear previous values after commit
	 *
	 * @param \PropelPHPParser $parser
	 */
	private function modifySaveAfterCommit(\PropelPHPParser $parser)
	{
		$method = $parser->findMethod('save');

		$clearArray = '            $this->previousValues = [];';
		$replaced = preg_replace('/(\$con->commit\(\);)/', "$1\n".$clearArray, $method);

		$parser->replaceMethod('save', $replaced);
	}

	private function overrideResetModifiedMethod(\PropelPHPParser $parser)
	{
		$parser->addMethodAfter('save', $this->behavior->renderTemplate('resetModified'));
	}

	private function addGetAllPreviousValuesMethod(\PropelPHPParser $parser)
	{
		$parser->addMethodAfter('save', $this->behavior->renderTemplate('allPreviousValues'));
	}

	private function modifyPostSaveEvent(\PropelPHPParser $parser)
	{
		$code = $parser->findMethod('save');

		foreach(['post_save', 'post_update', 'post_insert'] as $eventName)
		{
			$pattern = "/(dispatch\('propel.".$eventName."', new GenericEvent\(.+, array\()/";
			$replacedArg = sprintf('%s', "'previousValues' => \$previousValues");
			$code = preg_replace($pattern, sprintf('$1%s, ', $replacedArg), $code);
		}
		$code = preg_replace('(\$con->beginTransaction\(\);)', "\$0\n        \$previousValues = \$this->getAllPreviousValues();", $code);

		$parser->replaceMethod('save', $code);
	}
}