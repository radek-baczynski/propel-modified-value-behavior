

    /**
     * Get previous value of <?php echo $columnName;?> before modification
     *
     * @return <?php echo $type?>|null <?php echo PHP_EOL; ?>
     */
    public function getPreviousValueOf<?php echo $columnName;?>()
    {
        return $this->hasPreviousValueOf<?php echo $columnName;?>() ? $this->previousValues[<?php echo $peerColumn?>] : null;
    }
