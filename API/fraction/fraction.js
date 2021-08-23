var Fraction = function(numerator, denominator) {
	/* double argument invocation */
	if (typeof numerator !== 'undefined' && denominator) {
		if (
					(typeof(numerator) === 'number' ||   numerator instanceof Number) &&
					(typeof(denominator) === 'number' || denominator instanceof Number)
				){
			this.decimal = numerator / denominator;
		} else if (
					(typeof(  numerator) === 'string' ||   numerator instanceof String) && 
					(typeof(denominator) === 'string' || denominator instanceof String)
				) {
			// what are they?
			// hmm....
			// assume they are floats?
			this.decimal = parseFloat(numerator.replace(",",".")) / parseFloat(denominator.replace(",","."));
		}
		// TODO: should we handle cases when one argument is String and another is Number?
	/* single-argument invocation */
	} else if (typeof denominator === 'undefined') {
		var num = numerator; // swap variable names for legibility
		if (num instanceof Fraction) {
			this.decimal = num.decimal;
			
		} else if (typeof(num) === 'number' || num instanceof Number) {  // just a straight number init
			this.decimal = num;
			
		} else if (typeof(num) === 'string' || num instanceof String) {
			var a, b;  // hold the first and second part of the fraction, e.g. a = '1' and b = '2/3' in 1 2/3
			// or a = '2/3' and b = undefined if we are just passed a single-part number
			var arr = num.split('-');
			if (arr[0]) a = arr[0];
			if (arr[1]) b = arr[1];
			/* compound fraction e.g. 'A B/C' */
			//  if a is an integer ...
			if (a % 1 === 0 && b && b.match('/')) {
				return (new Fraction(a)).add(new Fraction(b));
			} else if (a && !b) {
				/* simple fraction e.g. 'A/B' */
				if ((typeof(a) === 'string' || a instanceof String) && a.match('/')) {
					// it's not a whole number... it's actually a fraction without a whole part written
					var f = a.split('/');
					this.decimal = parseFloat(f[0]) / parseFloat(f[1]);
				/* string floating point */
				} else if ((typeof(a) === 'string' || a instanceof String) && a.match('\.')) {
					return new Fraction(parseFloat(a.replace(",",".")));
				/* whole number e.g. 'A' */
				} else { // just passed a whole number as a string
					return new Fraction(parseInt(a));
				}
			} else {
				return undefined; // could not parse
			}
		}
	}
	this.normalize();
}
Fraction.prototype.clone = function() {
	return new Fraction(this.decimal);
}
Fraction.prototype.normalize = function() {
	if ( this.decimal === 0 ) {
		this.sign = 0;
		this.numerator = 0;
		this.denominator = 1;
		return this;
	}
	var abs = Math.abs(this.decimal);
	this.sign = this.decimal/abs;
	x = abs;
	var stack = 0;
	/*recursive function that transforms the fraction*/
	function recurs(x){
			stack++;
			var intgr = Math.floor(x); //get the integer part of the number
			var dec = (x - intgr); //get the decimal part of the number
			if (dec < 0.5/Math.pow(10, 4) || stack > 20) return [intgr,1]; //return the last integer you divided by
			var num = recurs(1/dec); //call the function again with the inverted decimal part
			return [intgr*num[0]+num[1],num[0]];
	}
	var t = recurs(this.decimal);
	this.numerator = t[0];
	this.denominator = t[1];
	this.decimal = this.numerator / this.denominator;
	return this;
}

Fraction.prototype.toString = function(improper = false, sep = '-') {
	improper = improper || false;
	var sign = this.sign === -1 ? '-' : '';
	var flooredDec = Math.floor(this.decimal);
	var whole = !improper ? (this.sign*Math.abs(flooredDec))+sep : '';
	if ( whole == '0'+sep ) whole = '';
	var numerator = !improper ? this.numerator-(flooredDec*this.denominator) : this.numerator;
	if ( numerator === 0 ) return flooredDec;
	return whole+numerator+'/'+this.denominator;
}

Fraction.prototype.add = function(b)
{
	var a = this.clone();
	if (b instanceof Fraction) {
			b = b.clone();
	} else {
			b = new Fraction(b);
	}
	a.decimal += b.decimal;
	return a.normalize();
}


Fraction.prototype.subtract = function(b)
{
	var a = this.clone();
	if (b instanceof Fraction) {
			b = b.clone();
	} else {
			b = new Fraction(b);
	}
	a.decimal -= b.decimal;
	return a.normalize();
}


Fraction.prototype.multiply = function(b)
{
	var a = this.clone();
	if (b instanceof Fraction) {
			b = b.clone();
	} else {
			b = new Fraction(b);
	}
	a.decimal *= b.decimal;
	return a.normalize();
}

Fraction.prototype.divide = function(b)
{
	var a = this.clone();
	if (b instanceof Fraction) {
			b = b.clone();
	} else {
			b = new Fraction(b);
	}
	a.decimal /= b.decimal;
	return a.normalize();
}

Fraction.prototype.equals = function(b)
{
    if (!(b instanceof Fraction)) {
        b = new Fraction(b);
    }
    // fractions that are equal should have equal normalized forms
    var a = this.clone().normalize();
    b = b.clone().normalize();
    return (a.decimal === b.decimal);
}
