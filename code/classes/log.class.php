<?php
	class Log
	{
		static private $instance = null;
		private $indent = 0;
		private $start = 0;
		private $indenttext = '  ';
		private $prefix = '';
		
		public static function _()
		{
			if (is_null(self::$instance))
			{
				self::$instance = new self();
			}
			return self::$instance;
		}
		
		public static function Error($msg)
		{
			self::_()->Write('ERROR '.$msg);
		}
		
		public function __construct($indenttext = '  ')
		{
			$this->start = microtime(true);
			$this->indenttext = $indenttext;
		}

		public function Write($txt, $indent = 0)
		{
			$time = str_pad(number_format((microtime(true)-$this->start), 3, '.', ''), 10, ' ', STR_PAD_LEFT).': '.$this->prefix;
			if ($this->indent > 0 or $indent > 0)
				$time .= str_repeat($this->indenttext, $this->indent + $indent);
			print $time.str_replace("\n", "\n".str_repeat(' ', strlen($time)), trim($txt))."\n";
		}
		
		public function Indent()
		{
			$this->indent++;
		}
		
		public function Unindent()
		{
			if ($this->indent > 0)
				$this->indent--;
		}

		public function SetPrefix($prefix)
		{
			if ($prefix === false)
				$this->prefix = '';
			else
				$this->prefix = $prefix.': ';
		}
	}
