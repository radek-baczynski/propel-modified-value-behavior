
		/**
		* @inheritdoc
		*/
		public function resetModified($col = null)
		{
			parent::resetModified($col);

			if (null !== $col && !empty($this->previousValues[$col]))
			{
				unset($this->previousValues[$col]);
			}
			else
			{
				$this->previousValues = [];
			}

			return $this;
		}