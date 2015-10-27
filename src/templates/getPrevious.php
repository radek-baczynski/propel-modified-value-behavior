

    /**
     * Get previous value of <?php echo $columnName;?> before modification
     *
     * @return <?php echo $type?>|null <?php echo PHP_EOL; ?>
     */
    public function getPreviousValueOf<?php echo $columnName;?>()
    {
        return !empty($this->previousValues[<?php echo $peerColumn?>]) ? $this->previousValues[<?php echo $peerColumn?>] : null;
    }
