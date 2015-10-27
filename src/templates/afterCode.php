if (true === $doSetPreviousValue && in_array(<?php echo $peerColumn; ?>, $this->modifiedColumns)) {
			$this->previousValues[<?php echo $peerColumn; ?>] = $previousValue;
		}