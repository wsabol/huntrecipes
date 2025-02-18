import Fraction from '../util/Fraction'
import axios from 'axios'
import $ from "jquery";
import HuntRecipes from '../HuntRecipes';
import PhotoUploadModal from '../widgets/PhotoUploadModal';

export default function() {
    const recipe_id = +$('.page-title[data-recipe-id]').data('recipe-id') || 0;
    let MasterMeasure = [];

    let photo_upload = new PhotoUploadModal(
        '#photo-upload-modal',
        '/api/v1/recipe/photo.php',
        {
            data_label: 'recipe_photo',
            file_name: 'recipe_' + recipe_id + '.jpg',
            callback: function(data){
                $('.btn-recipe-image').removeClass('disabled')
                $('#recipe-image').attr('src', data.image_filename).removeClass('d-none')
                this.hide()
            },
            post_data: {
                recipe_id: recipe_id
            }
        }
    )

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
            const $this = $(this);
            const new_status = !$this.hasClass('btn-favorite');

            $this.toggleClass('btn-outline-favorite btn-favorite');
            if (new_status) {
                $this.html('<i class="fa fa-heart"></i> My Favorite')
            } else {
                $this.html('<i class="fa fa-heart"></i> Save to my favorites')
            }

            HuntRecipes.setRecipeLiked(recipe_id, new_status)
        });

        // photo upload
        $('.btn-recipe-image').click(function(){
            if (HuntRecipes.current_user_id() === 0) {
                HuntRecipes.signUpModal.show()
                return;
            }
            photo_upload.show()
        })

        $('.btn-remove-photo').click(function(){
            const c = confirm('This will remove the existing photo and replace it with the generic one. Continue?')
            if (!c) {
                return false
            }

            $('.btn-remove-photo').addClass('disabled')

            axios.delete('/api/v1/recipe/photo.php?recipe_id=' + recipe_id)
                .then(response => {
                    response = response.data
                    $('.btn-remove-photo').removeClass('disabled')
                    $('#recipe-image').attr('src', response.data.image_filename).addClass('d-none')
                    photo_upload.hide()
                })
                .catch(error => {
                    $('.btn-remove-photo').removeClass('disabled')
                    console.error('Error removing photo:', error.response.data.message);
                    alert('There was an error removing the photo. Please try again.');
                })
        });
    }

    init()
}
