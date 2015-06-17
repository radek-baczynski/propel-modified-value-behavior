
	/**
	 * Check if <?php echo $columnName;?> was modified during request
	 *
	 * @return bool
	 */
	public function hasPreviousValueOf<?php echo $columnName;?>()
	{
		return array_key_exists(<?php echo $peerColumn?>, $this->previousValues);
	}
