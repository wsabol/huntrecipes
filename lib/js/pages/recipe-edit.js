import HuntRecipes from '../HuntRecipes';
import Fraction from '../util/Fraction';
import axios from 'axios'
import $ from "jquery";
import Autocomplete from "bootstrap5-autocomplete";

export default function() {
    const $recipe_form = $('#recipe-form')
    const $ingredients_container = $('#ingredient-container')
    const $instructions_container = $('#instruction-container')
    let Courses = [];
    let Cuisines = [];
    let Category = [];
    let Measures = [];
    let Ingredients = [];
    let IngredientPrep = [];

    const loadFormInputs = function() {
        let loads = [];

        loads.push(
            axios.get('/api/v1/course/list.php')
                .then(response => {
                    response = response.data;
                    console.log(response);
                    Courses = response.data;
                })
        )

        loads.push(
            axios.get('/api/v1/cuisine/list.php')
                .then(response => {
                    response = response.data;
                    console.log(response);
                    Cuisines = response.data;
                })
        )

        loads.push(
            axios.get('/api/v1/recipe-type/list.php')
                .then(response => {
                    response = response.data;
                    console.log(response);
                    Category = response.data;
                })
        )

        loads.push(
            axios.get('/api/v1/measure/list.php')
                .then(response => {
                    response = response.data;
                    console.log(response);
                    Measures = response.data;
                })
        )

        loads.push(
            axios.get('/api/v1/ingredient/list.php')
                .then(response => {
                    response = response.data;
                    console.log(response);
                    Ingredients = response.data;
                })
        )

        loads.push(
            axios.get('/api/v1/ingredient/prep-list.php')
                .then(response => {
                    response = response.data;
                    console.log(response);
                    IngredientPrep = response.data;
                })
        )

        return Promise.all(loads);
    }

    const getRecipeData = function() {
        const id = +$('[name=recipe_id]', $recipe_form).val();
        if (id === 0) {
            return Promise.resolve({})
        }

        return new Promise((res, rej) => {
            axios.get('/api/v1/recipe/?id=' + $('[name=recipe_id]', $recipe_form).val())
                .then(response => {
                    response = response.data;
                    console.log(response);
                    res(response.data);
                })
                .catch(rej)
        })
    }

    function buildIngredientRow(ingredient) {
        const row_number = $ingredients_container.find('.ingredient-row').length

        ingredient = ingredient || {};

        ingredient.ingredient_id = ingredient.ingredient_id || 0;
        ingredient.raw_ingredient_name = ingredient.raw_ingredient_name || '';
        ingredient.ingredient_prep = ingredient.ingredient_prep || '';
        ingredient.measure_id = ingredient.measure_id ?? 1;
        ingredient.optional_flag = +ingredient.optional_flag === 1;

        if (!!ingredient.amount) {
            let f1 = new Fraction(ingredient.amount)
            ingredient.amount = f1.toString();
        }

        let $row = $('<div class="row ingredient-row">')

        let $input_name = $('<input id="ingredient-input-' + row_number + '" name="raw_ingredient_name[]" class="form-control" type="text" placeholder="Grocery / Ingredient" required>').val(ingredient.raw_ingredient_name)
        let $input_prep = $('<input id="prep-input-' + row_number + '" name="ingredient_prep[]" class="form-control" type="text" placeholder="(optional) chopped, drained, etc" >').val(ingredient.ingredient_prep)
        let $input_amount = $('<input name="amount[]" class="form-control text-end" type="text" placeholder="Quantity" >').val(ingredient.amount);
        let $input_unit = $('<select class="form-select" name="measure_id[]" >')
        $input_unit.append(
            Measures.map(it => $('<option>').attr('value', it.id).text(it.name || 'n/a'))
        ).val(ingredient.measure_id)

        let check_id = 'input-optional-' + row_number;
        let $input_optional = $('<div class="form-check">').append([
            $('<input class="form-check-input" type="checkbox" value="1" name="ingredient_optional[]" id="' + check_id + '" >').prop('checked', ingredient.optional_flag),
            $('<label class="form-check-label" for="' + check_id + '" >').text('Optional')
        ])

        let $btn_remove = $('<button class="btn btn-sm btn-outline-error btn-ingredient-remove">').append($('<i class="fa fa-remove">'))

        $row.append([
            $('<div class="col-lg-4 mb-3">').append($input_name),
            $('<div class="col-lg-3 mb-3">').append($input_prep),
            $('<div class="col-lg-1 mb-3">').append($input_amount),
            $('<div class="col-lg-2 mb-3">').append($input_unit),
            $('<div class="col-lg-1 mb-3">').append($input_optional),
            $('<div class="col-lg-1 mb-3">').append($btn_remove),
        ])

        return $row;
    }

    function appendIngredientRow(ingredient) {
        let $row = buildIngredientRow(ingredient)
        $ingredients_container.append($row)

        const last_row = $ingredients_container.find('.ingredient-row').length - 1

        Autocomplete.init('#ingredient-input-' + last_row, {
            items: Ingredients.map(i => i.name),
        });
        Autocomplete.init('#prep-input-' + last_row, {
            items: IngredientPrep.map(i => i.name),
        });
    }

    function buildInstructionRow(instruction) {
        instruction = instruction ?? ''
        instruction = instruction.trim()

        let $row = $('<div class="row instruction-row">')

        let $input = $('<textarea name="instruction[]" rows=2 class="form-control" placeholder="Type what needs to happen at this step" required>').val(instruction)
        let $btn_remove = $('<button class="btn btn-sm btn-outline-error btn-instruction-remove">').append($('<i class="fa fa-remove">'))

        $row.append([
            $('<div class="col-lg-11 mb-3">').append($input),
            $('<div class="col-lg-1 mb-3">').append($btn_remove),
        ])

        return $row;
    }

    function renderRecipeData(recipe_data) {
        if (typeof recipe_data.title === 'undefined') {
            // new recipe
            $('.draft-badge').show();
            $('[name=published_flag][value="0"]', $recipe_form).prop('checked', true)
            $('.btn-ingredient-add').click();
            $('.btn-instruction-add').click();
            return;
        }

        $('[name=title]', $recipe_form).val(recipe_data.title)
        $('[name=course_id]', $recipe_form).val(recipe_data.course_id)
        $('[name=cuisine_id]', $recipe_form).val(recipe_data.cuisine_id)
        $('[name=recipe_type_id]', $recipe_form).val(recipe_data.type_id)
        $('[name=serving_measure_id]', $recipe_form).val(recipe_data.serving_measure_id)

        if (recipe_data.published_flag) {
            $('.draft-badge').hide();
            $('[name=published_flag][value="1"]', $recipe_form).prop('checked', true)
        } else {
            $('.draft-badge').show();
            $('[name=published_flag][value="0"]', $recipe_form).prop('checked', true)
        }


        $ingredients_container.empty();
        if (recipe_data.ingredients.length > 0) {
            for (const ingredient of recipe_data.ingredients) {
                appendIngredientRow(ingredient);
            }
        } else {
            $('.btn-ingredient-add').click();
        }

        $instructions_container.empty();
        if (recipe_data.instructions.length > 0) {
            for (const instruction of recipe_data.instructions) {
                let $row = buildInstructionRow(instruction)
                $instructions_container.append($row)
            }
        } else {
            $('.btn-instruction-add').click();
        }

        $('#image-preview').attr('src', recipe_data.image_filename).show()
    }

    function init() {

        let get_recipe = getRecipeData();

        let get_inputs = loadFormInputs()
            .then(() => {

                $('[name=course_id]', $recipe_form).append(
                    Courses.map(it => $('<option>').attr('value', it.id).text(it.name))
                )

                $('[name=cuisine_id]', $recipe_form).append(
                    Cuisines.map(it => $('<option>').attr('value', it.id).text(it.name))
                )

                $('[name=recipe_type_id]', $recipe_form).append(
                    Category.map(it => $('<option>').attr('value', it.id).text(it.name))
                )

                $('[name=serving_measure_id]', $recipe_form).append(
                    Measures.map(it => $('<option>').attr('value', it.id).text(it.name || 'Servings - n/a'))
                )
            })

        Promise.all([get_recipe, get_inputs])
            .then(responses => {
                renderRecipeData(responses[0])
            })

        $('.btn-ingredient-add').click(function(evt){
            evt.preventDefault()
            appendIngredientRow()
        })

        $ingredients_container.on('click', '.btn-ingredient-remove', function(evt){
            evt.preventDefault()
            const $this = $(this)
            if (confirm("Remove this ingredient from the recipe?")) {
                $this.closest('.ingredient-row').remove()
            }
        })

        $('.btn-instruction-add').click(function(evt){
            evt.preventDefault()
            $instructions_container.append(buildInstructionRow())
        })

        $instructions_container.on('click', '.btn-instruction-remove', function(evt){
            evt.preventDefault()
            const $this = $(this)
            if ($('[name="instruction[]"]').val() === '') {
                $this.closest('.instruction-row').remove()
                return true;
            }

            if (confirm("Remove this instruction from the recipe?")) {
                $this.closest('.instruction-row').remove()
            }
        })

        $('#image-upload').change(function (event) {
            let preview = $('#image-preview')[0]
            preview.src = URL.createObjectURL(event.target.files[0]);
            preview.onload = function () {
                URL.revokeObjectURL(preview.src);
            }
            preview.show();
        });

        $('.btn-recipe-delete').click(function(evt){
            evt.preventDefault()
            alert('delete')
        });

        $recipe_form.submit(function(evt){
            evt.preventDefault()
            const $btn_submit = $('button[type=submit]');
            const $success_alert = $('#alert-recipe-success');
            const $error_alert = $('#alert-recipe-error');

            if ($btn_submit.hasClass('disabled')) {
                return false;
            }

            const has_ingredients = $ingredients_container.find('.ingredient-row').length > 0
            if (!has_ingredients) {
                alert('Add ingredients to your recipe to continue!')
                return false;
            }

            const has_instructions = $instructions_container.find('.instruction-row').length > 0
            if (!has_instructions) {
                alert('Add instructions to your recipe to continue!')
                return false;
            }

            let payload = new FormData();
            payload.append('current_user_id', HuntRecipes.current_user_id());
            payload.append('recipe_id', $('[name=recipe_id]', $recipe_form).val());
            payload.append('title', $('[name=title]', $recipe_form).val());
            payload.append('course_id', $('[name=course_id]', $recipe_form).val());
            payload.append('cuisine_id', $('[name=cuisine_id]', $recipe_form).val());
            payload.append('type_id', $('[name=recipe_type_id]', $recipe_form).val());
            payload.append('serving_count', $('[name=serving_count]', $recipe_form).val());
            payload.append('serving_measure_id', $('[name=serving_measure_id]', $recipe_form).val());

            let ingredients = [];
            let instructions = [];

            $ingredients_container.find('.ingredient-row').each((i, row) => {
                let $row = $(row)
                ingredients.push({
                    raw_ingredient_name: $('[name="raw_ingredient_name[]"]', $row).val(),
                    ingredient_prep: $('[name="ingredient_prep[]"]', $row).val(),
                    amount: $('[name="amount[]"]', $row).val(),
                    measure_id: $('[name="measure_id[]"]', $row).val(),
                    optional: $('[name="ingredient_optional[]"]', $row).prop('checked'),
                })
            })

            payload.append('ingredients', JSON.stringify(ingredients));

            $instructions_container.find('.instruction-row').each((i, row) => {
                let $row = $(row)
                let value = $('[name="instruction[]"]', $row).val();
                value.replaceAll("\n", " ");
                instructions.push(value)
            })

            payload.append('instructions', JSON.stringify(instructions));

            // add image
            payload.append('recipe_image', document.querySelector('#image-upload').files[0]);

            $btn_submit.addClass('disabled')
            $success_alert.hide()
            $error_alert.hide()

            axios.post('/api/v1/recipe/', payload)
                .then(response => {
                    response = response.data;
                    console.log(response);

                    $btn_submit.removeClass('disabled')
                    renderRecipeData(response.data)
                    $success_alert.fadeIn();

                })
                .catch(error => {

                    $btn_submit.removeClass('disabled')

                    console.error(error.response.data.message);
                    $error_alert.find('.error-message').text(error.response.data.message)
                    $error_alert.fadeIn()
                })

            alert('submit')
        });
    }

    init()
}
