<?php

class Fraction {
	public $decimal;
	public $sign;
	public $numerator;
	public $denominator;
	
	function __construct ( $numerator, $denominator = null ) {
		/* double argument invocation */
		if ( $numerator !== null && $denominator !== null ) {
			if (
						is_numeric(str_replace(",", "", $numerator)) &&
						is_numeric(str_replace(",", "", $denominator))
					){
				$this->decimal = floatval(str_replace(",", "", $numerator)) / floatval(str_replace(",", "", $numerator));
			} 
			// TODO: should we handle cases when one argument is String and another is Number?
		/* single-argument invocation */
		} elseif ( $denominator === null ) {
			$num = $numerator; // swap variable names for legibility
			if ($num instanceof Fraction) {
				$this->decimal = $num->decimal;

			} elseif ( is_numeric(str_replace(",", "", $num)) ) {  // just a straight number init
				$this->decimal = floatval(str_replace(",", "", $num));

			} elseif ( is_numeric($num[0]) ) {
				$a = null;
				$b = null;  // hold the first and second part of the fraction, e.g. a = '1' and b = '2/3' in 1 2/3
				// or a = '2/3' and b = undefined if we are just passed a single-part number
				$arr = explode("-", $num, 2);
				if ( isset($arr[0]) ) $a = $arr[0];
				if ( isset($arr[1]) ) $b = $arr[1];
				/* compound fraction e.g. 'A B/C' */
				//  if a is an integer ...
				if ($a % 1 === 0 && $b !== null && strpos($b, '/') !== false ) {
					$this->decimal = floatval($a);
					$a = $this->add(new Fraction($b));
					$this->decimal = $a->decimal;
					$a = "";
				} elseif ( $a !== null && $b === null ) {
					/* simple fraction e.g. 'A/B' */
					if ( strpos($a, '/') !== false ) {
						// it's not a whole number... it's actually a fraction without a whole part written
						$f = explode("/", $a, 2);
						$this->decimal = floatval($f[0]) / floatval($f[1]);
					} else { // just passed a number as a string
						$this->decimal = floatval($a);
					}
				} else {
					return null; // could not parse
				}
			}
		}
		$this->normalize();
	}
	
	public function copy() {
		return new Fraction($this->decimal);
	}
	
	private function normalize() {
		if ( $this->decimal === 0 ) {
			$this->sign = 0;
			$this->numerator = 0;
			$this->denominator = 1;
			return $this;
		}
		$abs = $this->decimal == 0 ? 1 : abs($this->decimal);
		$this->sign = $this->decimal/$abs;
		
		$t = $this->recurs($this->decimal);
		$this->numerator = $t[0];
		$this->denominator = $t[1];
		$this->decimal = $this->numerator / $this->denominator;
		return $this;
	}
	
	private function recurs($x, $stack = 0){
		$stack++;
		$intgr = floor($x); //get the integer part of the number
		$dec = ($x - $intgr); //get the decimal part of the number
		if ($dec < 0.5/pow(10, 4) || $stack > 20) return [$intgr,1]; //return the last integer you divided by
		$num = $this->recurs(1/$dec, $stack); //call the function again with the inverted decimal part
		return [$intgr*$num[0]+$num[1],$num[0]];
	}
	
	public function toString($improper = false, $sep = '-') {
		$improper = $improper || false;
		$sign = $this->sign === -1 ? '-' : '';
		$flooredDec = floor($this->decimal);
		$whole = !$improper ? ($this->sign*abs($flooredDec)).$sep : '';
		if ( $whole == '0'.$sep ) $whole = '';
		$numerator = !$improper ? $this->numerator-($flooredDec*$this->denominator) : $this->numerator;
		if ( $numerator == 0 ) return $flooredDec;
		return $whole.$numerator.'/'.$this->denominator;
	}
	
	public function add($b) {
		$a = $this->copy();
		if ($b instanceof Fraction) {
			$b = $b->copy();
		} else {
			$b = new Fraction($b);
		}
		$a->decimal += $b->decimal;
		return $a->normalize();
	}
	
	public function subtract($b) {
		$a = $this->copy();
		if ($b instanceof Fraction) {
			$b = $b->copy();
		} else {
			$b = new Fraction($b);
		}
		$a->decimal -= $b->decimal;
		return $a->normalize();
	}
	
	public function multiply($b) {
		$a = $this->copy();
		if ($b instanceof Fraction) {
			$b = $b->copy();
		} else {
			$b = new Fraction($b);
		}
		$a->decimal *= $b->decimal;
		return $a->normalize();
	}
	
	public function divide($b) {
		$a = $this->copy();
		if ($b instanceof Fraction) {
			$b = $b->copy();
		} else {
			$b = new Fraction($b);
		}
		$a->decimal /= $b->decimal;
		return $a->normalize();
	}
	
	public function equals($b) {
		if (!($b instanceof Fraction)) {
			$b = new Fraction($b);
		}
		// fractions that are equal should have equal normalized forms
		$a = $this->copy();
		$a->normalize();
		$b = $b->copy();
		$b->normalize();
		return ($b->decimal === $b->decimal);
	}
	
}

?>