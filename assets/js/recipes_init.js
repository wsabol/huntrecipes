var MasterMeasure = [
   {
      "id":"0",
      "name":"",
      "name_plural":"",
      "abbr":"",
      "abbr_alt":null,
      "measure_type_id":"0",
      "general_unit_conversion":1.00000000,
      "metric_flag":"0",
      "frac_perm":"0",
      "frac":[

      ]
   },
   {
      "id":"1",
      "name":"teaspoon",
      "name_plural":"teaspoons",
      "abbr":"tsp",
      "abbr_alt":"t",
      "measure_type_id":"1",
      "general_unit_conversion":1.00000000,
      "metric_flag":"0",
		 	"frac_perm":"1",
      "frac":[
         "1\/4",
         "1\/2",
         "3\/4"
      ]
   },
   {
      "id":"2",
      "name":"tablespoon",
      "name_plural":"tablespoons",
      "abbr":"tbsp",
      "abbr_alt":"T tbl",
      "measure_type_id":"1",
      "general_unit_conversion":3.00000000,
      "metric_flag":"0",
		 "frac_perm":"0",
      "frac":[
         "1\/4",
         "1\/2",
         "3\/4"
      ]
   },
   {
      "id":"3",
      "name":"fluid ounce",
      "name_plural":"fluid ounces",
      "abbr":"fl-oz",
      "abbr_alt":"oz",
      "measure_type_id":"1",
      "general_unit_conversion":6.00000000,
      "metric_flag":"0",
		 "frac_perm":"0",
      "frac":[

      ]
   },
   {
      "id":"4",
      "name":"cup",
      "name_plural":"cups",
      "abbr":"c",
      "abbr_alt":"c",
      "measure_type_id":"1",
      "general_unit_conversion":48.00000000,
      "metric_flag":"0",
		 "frac_perm":"1",
      "frac":[
         "1\/4",
         "1\/3",
         "1\/2",
         "2\/3",
         "3\/4"
      ]
   },
   {
      "id":"5",
      "name":"pint",
      "name_plural":"pints",
      "abbr":"p",
      "abbr_alt":"pt",
      "measure_type_id":"1",
      "general_unit_conversion":96.00000000,
      "metric_flag":"0",
		 "frac_perm":"0",
      "frac":[

      ]
   },
   {
      "id":"6",
      "name":"quart",
      "name_plural":"quarts",
      "abbr":"q",
      "abbr_alt":"qt",
      "measure_type_id":"1",
      "general_unit_conversion":192.00000000,
      "metric_flag":"0",
		 "frac_perm":"0",
      "frac":[

      ]
   },
   {
      "id":"7",
      "name":"gallon",
      "name_plural":"gallons",
      "abbr":"gal",
      "abbr_alt":"g",
      "measure_type_id":"1",
      "general_unit_conversion":768.00000000,
      "metric_flag":"0",
		 "frac_perm":"0",
      "frac":[

      ]
   },
   {
      "id":"8",
      "name":"ounce",
      "name_plural":"ounces",
      "abbr":"oz",
      "abbr_alt":"oz",
      "measure_type_id":"2",
      "general_unit_conversion":1.00000000,
      "metric_flag":"0",
		 "frac_perm":"0",
      "frac":[

      ]
   },
   {
      "id":"9",
      "name":"pound",
      "name_plural":"pounds",
      "abbr":"lb",
      "abbr_alt":"lbs",
      "measure_type_id":"2",
      "general_unit_conversion":16.00000000,
      "metric_flag":"0",
		 "frac_perm":"0",
      "frac":[

      ]
   },
   {
      "id":"10",
      "name":"pinch",
      "name_plural":"pinches",
      "abbr":"pinch",
      "abbr_alt":"pinch",
      "measure_type_id":"1",
      "general_unit_conversion":0.06250000,
      "metric_flag":"0",
		 "frac_perm":"0",
      "frac":[

      ]
   },
   {
      "id":"11",
      "name":"dash",
      "name_plural":"dashes",
      "abbr":"dash",
      "abbr_alt":"dash",
      "measure_type_id":"1",
      "general_unit_conversion":0.12500000,
      "metric_flag":"0",
		 "frac_perm":"0",
      "frac":[

      ]
   },
   {
      "id":"12",
      "name":"smidgen",
      "name_plural":"smidgens",
      "abbr":"smidgen",
      "abbr_alt":"smidgen",
      "measure_type_id":"1",
      "general_unit_conversion":0.03125000,
      "metric_flag":"0",
		 "frac_perm":"0",
      "frac":[

      ]
   },
   {
      "id":"14",
      "name":"sprig",
      "name_plural":"sprigs",
      "abbr":"",
      "abbr_alt":null,
      "measure_type_id":"0",
      "general_unit_conversion":0.00000000,
      "metric_flag":"0",
		 "frac_perm":"0",
      "frac":[

      ]
   },
   {
      "id":"15",
      "name":"drop",
      "name_plural":"drops",
      "abbr":"",
      "abbr_alt":null,
      "measure_type_id":"1",
      "general_unit_conversion":0.01014420,
      "metric_flag":"0",
		 "frac_perm":"0",
      "frac":[

      ]
   },
   {
      "id":"16",
      "name":"liter",
      "name_plural":"liters",
      "abbr":"L",
      "abbr_alt":null,
      "measure_type_id":"1",
      "general_unit_conversion":202.88400000,
      "metric_flag":"1",
		 "frac_perm":"0",
      "frac":[

      ]
   },
   {
      "id":"17",
      "name":"milliliter",
      "name_plural":"milliliters",
      "abbr":"mL",
      "abbr_alt":"ml",
      "measure_type_id":"1",
      "general_unit_conversion":0.20288400,
      "metric_flag":"1",
		 "frac_perm":"0",
      "frac":[

      ]
   },
   {
      "id":"18",
      "name":"tad",
      "name_plural":"tads",
      "abbr":"tad",
      "abbr_alt":null,
      "measure_type_id":"1",
      "general_unit_conversion":0.25000000,
      "metric_flag":"0",
		 "frac_perm":"0",
      "frac":[

      ]
   }
];

function friendlyAmount( gen_amt, measure_type_id ) {
  var value = { };
	value.formatted = '';
	value.decimal = 0;
	
	if ( gen_amt <= 0 ) {
    return value;
  }
  
  if ( measure_type_id == 0 ) {
    var f0 = new Fraction( gen_amt );
		value.formatted = f0.toString();
    value.decimal = f0.decimal;
    return value;
  }
  
  var Measure = [];
  for ( var i = 0; i < MasterMeasure.length; i++ ) {
    if ( MasterMeasure[i].measure_type_id == measure_type_id ) {
      Measure.push( MasterMeasure[i] );
    }
  }
  Measure.sort(function(a, b){
		var a0 = parseFloat(a.general_unit_conversion);
		var b0 = parseFloat(b.general_unit_conversion);
		if ( a0 < b0 ) return 1;
		if ( a0 > b0 ) return -1;
		return 0;
	});
  var tmp_value = '';
  var frac = [];
  var values = [];
	var convert, remainder, floor, vEnd;
  var f1, f_vEnd, f_test;
  
  for ( i = 0; i < Measure.length && gen_amt > 0.0001; i++ ) {
    convert = gen_amt / Measure[i].general_unit_conversion;
    frac = Measure[i].frac;
		
    if ( Math.abs(Math.floor(convert) - convert) < 0.0001 || 
				( frac.length > 0 && Measure[i].frac_perm == 1 ) ) {
      f1 = new Fraction(convert);
      tmp_value = f1.toString();
      values = (tmp_value+'').split('-');
      vEnd = values[values.length - 1];
      if ( !frac.includes(vEnd) && vEnd.includes('/') ) {
				floor = 0;
				f_vEnd = new Fraction(vEnd);
				
				for ( var j = 0; j < frac.length; j++ ) {
					f_test = new Fraction(frac[j]);
					if ( f_vEnd.decimal < f_test.decimal ) {
						break;
					} else {
						floor = f_test.decimal;
					}
				}
      	f1 = new Fraction( floor );
      }
      
      if ( f1.toString() != '0' ) {
        if ( value.formatted.length === 0 ) {
          value.decimal = f1.decimal;
        }
        value.formatted = value.formatted + ( value.formatted.length > 0 ? ' + ' : '' ) + f1.toString() + ' ' + Measure[i].abbr;
        gen_amt = ( convert - f1.decimal ) * Measure[i].general_unit_conversion;
      }
    } else if ( i == Measure.length - 1 ) {
			
			if ( measure_type_id == 1 ) {
				f1 = new Fraction( Math.round(convert) );
				if ( f1.toString() != '0' ) {
					value.formatted = value.formatted + ( value.formatted.length > 0 ? ' + ' : '' ) + f1.toString() + ' ' + Measure[i].abbr;
					if ( value.decimal === 0 ) {
						value.decimal = f1.decimal;
					}
				}
			}
			else {
				f1 = new Fraction( convert );
				if ( f1.decimal !== 0 ) {
					value.formatted = value.formatted + ( value.formatted.length > 0 ? ' + ' : '' ) + (Math.round(f1.decimal*100)/100) + ' ' + Measure[i].abbr;
					if ( value.decimal === 0 ) {
						value.decimal = f1.decimal;
					}
				}
			}
    }
  }
  
	return value;
}

function getIngredientSearchList() {
	var ingrList = '';
	$.each( $('.recipefinder .ingredients .ingr-search-wrapper .ingr-search'), function(i, el){
		ingrList += ( i > 0 ? ',' : '' ) + $(el).data('ingredient-id');
	});
	return ingrList;
}
