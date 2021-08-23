<?php
require_once('fraction.php');

$val = "1-4/6";
$f = new Fraction($val);
echo $f->toString();

?>