class Fraction {
    decimal = 0
    sign = 0
    numerator = 0
    denominator = 1

    constructor(numerator, denominator = undefined) {

        /* double argument invocation */
        if (typeof numerator !== 'undefined' && typeof denominator !== 'undefined') {
            this.decimal = parseFloat(numerator) / parseFloat(denominator);

        }
        else if (typeof numerator !== 'undefined') {

            if (typeof numerator === 'number') {  // just a straight number init
                this.decimal = parseFloat(numerator)

            } else if (typeof numerator === 'string') {
                const parts = numerator.split('-');
                this.decimal = parseFloat(eval(parts[0] ?? "0")) + parseFloat(eval(parts[1] ?? "0"));
            }
        }

        this.normalize();
    }

    clone() {
        return new Fraction(this.decimal);
    }

    normalize() {
        if (this.decimal === 0) {
            this.sign = 0;
            this.numerator = 0;
            this.denominator = 1;
            return this;
        }

        this.sign = Math.sign(this.decimal)
        let stack = 0;

        /*recursive function that transforms the fraction*/
        function recurs(x) {
            stack++;
            let integer = Math.floor(x); // get the integer part of the number
            let dec = (x - integer); // get the decimal part of the number
            if (dec < 0.5 / Math.pow(10, 4) || stack > 20) return [integer, 1]; // return the last integer you divided by
            let num = recurs(1 / dec); // call the function again with the inverted decimal part
            return [integer * num[0] + num[1], num[0]];
        }

        const t = recurs(this.decimal);
        this.numerator = t[0];
        this.denominator = t[1];
        this.decimal = this.numerator / this.denominator;
        return this;
    }

    /**
     *
     * @param improper
     * @param sep
     * @returns string
     */
    toString(improper = false, sep = '-') {
        improper = improper || false;
        let sign_symbol = this.sign === -1 ? '-' : '';
        let flooredDec = Math.floor(this.decimal);
        let whole = !improper ? (this.sign * Math.abs(flooredDec)) + sep : '';
        if (whole === '0' + sep) whole = '';
        let numerator = !improper ? this.numerator - (flooredDec * this.denominator) : this.numerator;
        if (numerator === 0) return '' + flooredDec;
        return sign_symbol + whole + numerator + '/' + this.denominator;
    }

    add(b) {
        let a = this.clone();
        if (b instanceof Fraction) {
            b = b.clone();
        } else {
            b = new Fraction(b);
        }
        a.decimal += b.decimal;
        return a.normalize();
    }

    subtract(b) {
        let a = this.clone();
        if (b instanceof Fraction) {
            b = b.clone();
        } else {
            b = new Fraction(b);
        }
        a.decimal -= b.decimal;
        return a.normalize();
    }

    multiply(b) {
        let a = this.clone();
        if (b instanceof Fraction) {
            b = b.clone();
        } else {
            b = new Fraction(b);
        }
        a.decimal *= b.decimal;
        return a.normalize();
    }

    divide(b) {
        let a = this.clone();
        if (b instanceof Fraction) {
            b = b.clone();
        } else {
            b = new Fraction(b);
        }
        a.decimal /= b.decimal;
        return a.normalize();
    }

    equals(b) {
        if (!(b instanceof Fraction)) {
            b = new Fraction(b);
        }
        // fractions that are equal should have equal normalized forms
        let a = this.clone().normalize();
        b = b.clone().normalize();
        return (a.decimal === b.decimal);
    }

    is_partial_allowed(fractions_allowed) {
        const str = this.toString()
        const parts = str.split('-');
        const last = parts[parts.length - 1];
        if (!last.includes("/")) {
            return true;
        }
        return fractions_allowed.includes(last);
    }

}

export default Fraction;
