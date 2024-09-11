import HuntRecipes from '../HuntRecipes';
import Fraction from '../util/Fraction'
import axios from 'axios'
import $ from "jquery";

export default function() {
    let MasterMeasure = [];

    const loadMeasures = function() {
        axios.get('/api/v1/measure/list.php')
            .then(response => {
                response = response.data;
                console.log(response);

                MasterMeasure = response.data;
            })
    };

    function friendlyAmount( gen_amt, measure_type_id ) {
        var value = { };
        value.formatted = '';
        value.decimal = 0;

        if ( gen_amt <= 0 ) {
            return value;
        }

        if ( measure_type_id === 0 ) {
            var f0 = new Fraction( gen_amt );
            value.formatted = f0.toString();
            value.decimal = f0.decimal;
            return value;
        }

        var Measure = [];
        for ( var i = 0; i < MasterMeasure.length; i++ ) {
            if ( MasterMeasure[i].measure_type == measure_type_id ) {
                Measure.push( MasterMeasure[i] );
            }
        }
        Measure.sort(function(a, b){
            var a0 = parseFloat(a.base_unit_conversion);
            var b0 = parseFloat(b.base_unit_conversion);
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
            convert = gen_amt / Measure[i].base_unit_conversion;
            frac = Measure[i].fractions_allowed;

            if ( Math.abs(Math.floor(convert) - convert) < 0.0001 || frac.length > 0 ) {
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
                    gen_amt = ( convert - f1.decimal ) * Measure[i].base_unit_conversion;
                }
            } else if ( i === Measure.length - 1 ) {

                if ( measure_type_id === 1 ) {
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

    function updateIngredientAmounts(serving_count) {
        var test = new Fraction( $('#input-serving').val() );
        if ( test.decimal === undefined ) {
            var reset = new Fraction( $('#input-serving').data('org-serving') );
            $('#input-serving').val( org.toString() );
        }

        var newServ = new Fraction( $('#input-serving').val() );
        var orgServ = new Fraction( $('#input-serving').data('org-serving') );

        var multiplier = newServ.decimal / orgServ.decimal;
        $.each( $('.recipe-ingredients .ingredient-amount:not(.no-amount)'), function(i, el){
            var measure_type_id = $(el).data('measure-type-id');
            var val = new Fraction( $(el).data('org-amount') );

            var newVal = friendlyAmount(val.decimal * multiplier, measure_type_id);
            console.log(newVal);
            $(el).text( newVal.formatted );

            var $ingrName = $(el).next();
            var ingrs = $ingrName.text().split('; ');
            if ( (newVal.decimal > 1 && measure_type_id != 2) || (newVal.decimal == 0 && measure_type_id == 0) ) {
                ingrs[0] = $ingrName.data('name-plural');
            } else {
                ingrs[0] = $ingrName.data('name');
            }
            $ingrName.text( ingrs.join('; ') );
        });
    }

    function init() {

        loadMeasures()

        $('#input-serving').blur(function(){
            updateIngredientAmounts( $(this) );
        });

        $('#input-serving').keyup(function(e){
            if ( e.which == 13 ) {
                updateIngredientAmounts( $(this) );
            }
        });

        $('.btn-save-to-favorites').click(function(e){
            e.preventDefault();
            var $btn = $(this);

            const recipe_id = $btn.attr('data-id');

        //     var favorite_flag = 1;
        //     if ( $(this).parent().hasClass('favorite-recipe') ) {
        //         // already saved remove from table
        //         favorite_flag = 0;
        //     }
        //
        //     $(this).find('i').addClass('fa-spin fa-fw');
        //     $.ajax({
        //         url: '/ajax-json/spFavoriteRecipe.json.php',
        //         type: 'GET',
        //         data: {
        //             recipe_id: recipe_id,
        //     favorite_flag: favorite_flag
        // },
        //     success: function( response ) {
        //         //console.log(response);
        //         if ( response.success == 1 && favorite_flag == 1 ) {
        //             $btn.parent().addClass('favorite-recipe');
        //             $btn.find('span').text('Favorited');
        //         } else if ( response.success == 1 && favorite_flag == 0 ) {
        //             $btn.parent().removeClass('favorite-recipe');
        //             $btn.find('span').text('Save to favorites');
        //         } else {
        //             console.log(response.query);
        //         }
        //
        //         $btn.find('i').removeClass('fa-spin fa-fw');
        //     }
        // });

            return false;
        });
    }

    init()
}
