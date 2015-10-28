
	if (null !== $col && array_key_exists($col, $this->previousValues))
	{
		unset($this->previousValues[$col]);
	}
	else
	{
		$this->previousValues = [];
	}
