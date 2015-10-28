<?php

namespace Radekb\ModifiedValueBehavior;

use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Column;
use Propel\Generator\Util\PhpParser;

class ModifiedValueModelBuilderModifier
{
	/** @var  ModifiedValueBehavior */
	protected $behavior;

	/**
	 * @param ModifiedValueBehavior $behavior
	 */
	function __construct(ModifiedValueBehavior $behavior)
	{
		$this->behavior = $behavior;
	}

	/**
	 * @param $builder ObjectBuilder
	 *
	 * @return string
	 */
	public function objectAttributes($builder)
	{
		return $this->behavior->renderTemplate('objectAttributes');
	}

	public function objectMethods($builder)
	{
		$str = $this->behavior->renderTemplate('allPreviousValues');

		return $str;
	}

	/**
	 * Modify setters to store previous values, add useful has/get previous value methods for model
	 *
	 * @param $script
	 */
	public function objectFilter(&$script)
	{
		$parser = new PhpParser($script, true);

		foreach ($this->behavior->getTable()->getColumns() as $column)
		{
			$setterName = 'set' . $column->getPhpName();

			$oldCode    = $parser->findMethod($setterName);

			$newCode = $this->modifySetterBeginning($oldCode, $column, $parser);
			$newCode = $this->modifySetterBeforeReturn($newCode, $column, $parser);

			$parser->replaceMethod($setterName, $newCode);

			$parser->addMethodAfter($setterName, $this->behavior->renderTemplate('hasPrevious', [
				'columnName' => $column->getPhpName(),
				'peerColumn' => $column->getFQConstantName(),
			]));

			$parser->addMethodAfter($setterName, $this->behavior->renderTemplate('getPrevious', [
				'columnName' => $column->getPhpName(),
				'peerColumn' => $column->getFQConstantName(),
				'type'       => $column->getPhpType(),
			]));
		}

		$resetModifiedCode = $parser->findMethod('resetModified');
		$resetPreviousCode = $this->behavior->renderTemplate('resetModified');

		$regexp = "~
			function                 #function keyword
			\s+                      #any number of whitespaces
			(?P<function_name>.*?)   #function name itself
			\s*                      #optional white spaces
			(?P<parameters>\(.*?\))  #function parameters
			\s*                      #optional white spaces
			(?P<body>\{(?P<bodyCode>.*?)\}$)        #body and body code of a function
        ~six";

		preg_match_all($regexp, $resetModifiedCode, $matches);

		if(empty($matches['bodyCode']))
		{
			throw new \Exception('Cannot parse method');
		}

		$bodyCode = $matches['bodyCode'][0].$resetPreviousCode;
		$resetModifiedCode = str_replace($matches['bodyCode'][0], $bodyCode, $resetModifiedCode);

		$parser->replaceMethod('resetModified', $resetModifiedCode);

		$script = $parser->getCode();
	}

	/**
	 * @param                  $code
	 * @param Column           $column
	 * @param PhpParser $parser
	 *
	 * @return mixed
	 */
	private function modifySetterBeginning($code, Column $column, PhpParser $parser)
	{
		$beforeCode = $this->behavior->renderTemplate('beforeCode', [
			'peerColumn' => $column->getFQConstantName(),
			'columnName' => $column->getPhpName(),
		]);

		$newCode = preg_replace('/^([^\{]*\{)/', "$1\n        " . $beforeCode, $code);

		return $newCode;
	}

	/**
	 * @param                  $code
	 * @param Column           $column
	 * @param PhpParser $parser
	 *
	 * @return mixed
	 */
	private function modifySetterBeforeReturn($code, Column $column, PhpParser $parser)
	{
		$afterCode = $this->behavior->renderTemplate('afterCode', [
			'peerColumn' => $column->getFQConstantName(),
			'columnName' => $column->getPhpName(),
		]);

		$newCode = preg_replace('/(return .+;)/', $afterCode . "\n        $1", $code);

		return $newCode;
	}

	/**
	 * Clear previous values after commit
	 *
	 * @return string
	 */
	public function postSave()
	{
		return $this->behavior->renderTemplate('postSave', []);
	}
}