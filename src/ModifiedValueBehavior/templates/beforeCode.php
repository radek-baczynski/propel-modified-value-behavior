$doSetPreviousValue = false;

if (false === $this->hasPreviousValueOf<?php echo $columnName;?>()) {
	        $previousValue = $this->get<?php echo $columnName;?>();
			$doSetPreviousValue = true;
		}