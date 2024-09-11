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

    function friendlyAmount(base_amount, measure_type) {
        let value = {};
        value.formatted = '';
        value.decimal = 0;

        if (base_amount <= 0) {
            return value;
        }

        if (measure_type === 0) {
            let f0 = new Fraction(base_amount);
            value.formatted = f0.toString();
            value.decimal = f0.decimal;
            return value;
        }

        let Measures = MasterMeasure.filter(m => +m.measure_type === +measure_type)
        Measures.sort((a, b) => b.base_unit_conversion - a.base_unit_conversion);

        const EPSILON = 0.0001;
        let formatted_values = [];
        let remaining_base_amount = base_amount;
        value.decimal = false

        for (let i = 0; i < Measures.length && remaining_base_amount > EPSILON; i++) {
            const measure = Measures[i];

            let current_amount = remaining_base_amount / measure.base_unit_conversion;

            if (Math.abs(Math.floor(current_amount) - current_amount) < EPSILON || measure.fractions_allowed.length > 0) {

                // special case, is less than 1.25 pounds, continue to ounces
                if (measure.id === 9 && current_amount < 1.25) {
                    continue;
                }

                let f1 = new Fraction(current_amount);
                if (!f1.is_partial_allowed(measure.fractions_allowed)) {
                    current_amount = Math.floor(current_amount);
                    f1 = new Fraction(current_amount);
                }

                remaining_base_amount -= (f1.decimal * measure.base_unit_conversion);

                if (f1.decimal > EPSILON) {
                    formatted_values.push(f1.toString() + " " + measure.abbr);

                    if (value.decimal === false) {
                        value.decimal = f1.decimal;
                    }
                }

                continue;
            }

            if (i === Measures.length - 1 && current_amount > EPSILON) {
                if (measure_type === 2) {
                    formatted_values.push((Math.round(100 * current_amount) / 100) + " " + measure.abbr);

                    if (value.decimal === false) {
                        value.decimal = (Math.round(100 * current_amount) / 100);
                    }
                } else {
                    let f1 = new Fraction(Math.round(current_amount));
                    if (f1.decimal > EPSILON) {
                        formatted_values.push(f1.toString() + " " + measure.abbr);
                    }

                    if (value.decimal === false) {
                        value.decimal = f1.decimal;
                    }
                }
            }
        }

        value.decimal = +value.decimal;
        value.formatted = formatted_values.join('-')
        return value;
    }

    function updateIngredientAmounts(service_count, org_service_count) {
        const newServ = new Fraction(service_count);
        const orgServ = new Fraction(org_service_count);
        const multiplier = newServ.decimal / orgServ.decimal;

        $.each($('.recipe-ingredients .ingredient-amount:not(.no-amount)'), function(i, el) {
            const measure_type_id = $(el).data('measure-type-id');
            const val = new Fraction(+$(el).data('org-amount'));

            const newVal = friendlyAmount(val.decimal * multiplier, measure_type_id);
            console.log(newVal);
            $(el).html(newVal.formatted);

            const $ingrName = $(el).next();
            let ingrs = $ingrName.text().split('; ');

            if ((newVal.decimal > 1 && measure_type_id !== 2) || (newVal.decimal === 0 && measure_type_id === 0)) {
                ingrs[0] = $ingrName.data('name-plural');
            } else {
                ingrs[0] = $ingrName.data('name');
            }

            $ingrName.text(ingrs.join('; '));
        });
    }

    function init() {

        loadMeasures()

        $('#input-serving').blur(function() {
            const org = +$(this).data('org-serving');
            let test = new Fraction(this.value);
            if (test.decimal === undefined) {
                let reset = new Fraction(org);
                this.value = reset.toString();
            }

            updateIngredientAmounts(this.value, org);
        });

        $('#input-serving').keyup(function(e) {
            const org = +$(this).data('org-serving');
            let test = new Fraction(this.value);
            if (test.decimal === undefined) {
                let reset = new Fraction(org);
                this.value = reset.toString();
            }

            if (e.which === 13) {
                updateIngredientAmounts(this.value, org);
            }
        });

        $('.btn-save-to-favorites').click(function(e) {
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
