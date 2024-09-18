<?php

namespace HuntRecipes\Measure;

class Fraction {

    /**
     * @var float $decimal the floating point value of the fraction
     */
    public float $decimal;

    /**
     * @var int $sign 1 positive; -1 negative
     */
    public int $sign;

    private int $numerator;
    private int $denominator;

    /**
     * @param mixed $numerator
     * @param mixed $denominator optional
     */
    public function __construct($numerator, $denominator = null) {

        if ($numerator !== null && $denominator !== null) {
            // double argument invocation

            if (
                is_numeric(str_replace(",", "", $numerator)) &&
                is_numeric(str_replace(",", "", $denominator))
            ) {
                $this->decimal = floatval(str_replace(",", "", $numerator)) / floatval(str_replace(",", "", $numerator));
            }

        } elseif ($denominator === null) {
            // single-argument invocation

            $num = $numerator; // swap variable names for legibility
            if ($num instanceof Fraction) {
                $this->decimal = $num->decimal;

            } elseif (is_numeric(str_replace(",", "", $num))) {  // just a straight number init
                $this->decimal = floatval(str_replace(",", "", $num));

            } elseif (is_numeric($num[0])) {

                // hold the first and second part of the fraction, e.g. a = '1' and b = '2/3' in 1 2/3
                // or a = '2/3' and b = undefined if we are just passed a single-part number
                $arr = explode("-", $num, 2);
                $a = isset($arr[0]) ? (int)@$arr[0] : null;
                $b = isset($arr[1]) ? (int)@$arr[1] : null;

                if (is_int($a) && $b !== null && str_contains($b, '/')) {
                    // compound fraction e.g. 'A B/C'
                    $this->decimal = floatval($a);
                    $a = $this->add(new Fraction($b));
                    $this->decimal = $a->decimal;

                } elseif ($a !== null && str_contains($a, '/') && $b === null) {
                    // simple fraction e.g. 'A/B'
                    $f = explode("/", $a, 2);
                    $this->decimal = floatval($f[0]) / floatval($f[1]);
                } elseif ($a !== null && $b === null) {
                    // just passed a number as a string
                    $this->decimal = floatval($a);
                }
            }
        }
        $this->normalize();
    }

    public function __set($name, $value) {
        trigger_error("this is a readonly function", E_USER_ERROR);
    }

    /**
     * @return Fraction
     */
    public function copy(): Fraction {
        return new Fraction($this->decimal);
    }

    /**
     * @return Fraction
     */
    private function normalize(): Fraction {
        if (!$this->decimal) {
            $this->decimal = 0;
            $this->sign = 0;
            $this->numerator = 0;
            $this->denominator = 1;
            return $this;
        }

        $this->sign = (int)round($this->decimal / abs($this->decimal));

        $t = $this->recurs($this->decimal);
        $this->numerator = $t[0];
        $this->denominator = $t[1];
        $this->decimal = $this->numerator / $this->denominator;
        return $this;
    }

    private function recurs($x, $stack = 0) {
        $stack++;
        $integer = floor($x); // get the integer part of the number
        $dec = ($x - $integer); // get the decimal part of the number
        if ($dec < 0.5 / pow(10, 4) || $stack > 20) return [$integer, 1]; // return the last integer you divided by
        $num = $this->recurs(1 / $dec, $stack); // call the function again with the inverted decimal part
        return [$integer * $num[0] + $num[1], $num[0]];
    }

    /**
     * Formats object as a string
     *
     * @param bool $improper
     * @param string $sep between the whole number and the fraction
     * @return string
     */
    public function toString(bool $improper = false, string $sep = '-'): string {
        $sign = $this->sign === -1 ? '-' : '';
        $flooredDec = floor($this->decimal);
        $whole = !$improper ? ($this->sign * abs($flooredDec)) . $sep : '';
        if ($whole == '0' . $sep) $whole = '';
        $numerator = !$improper ? $this->numerator - ($flooredDec * $this->denominator) : $this->numerator;
        if ($numerator == 0) return $flooredDec;
        return $sign . $whole . $numerator . '/' . $this->denominator;
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
        return ($a->decimal === $b->decimal);
    }

    public function is_partial_allowed(array $fractions_allowed): bool {
        $string = $this->toString();
        $parts = explode("-", $string);
        $last = end($parts);
        if (!str_contains($last, "/")) {
            return true;
        }

        return in_array($last, $fractions_allowed);
    }
}
