import HuntRecipes from '../HuntRecipes';
import Fraction from '../util/Fraction';
import axios from 'axios'
import $ from "jquery";

export default function() {
    const $recipe_form = $('#recipe-form')
    const $ingredients_container = $('#ingredient-container')
    const $instructions_container = $('#instruction-container')
    let Courses = [];
    let Cuisines = [];
    let Category = [];
    let Measures = [];

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

        let $input_name = $('<input name="raw_ingredient_name[]" class="form-control" type="text" placeholder="Grocery / Ingredient" required>').val(ingredient.raw_ingredient_name)
        let $input_prep = $('<input name="ingredient_prep[]" class="form-control" type="text" placeholder="(optional) chopped, drained, etc" >').val(ingredient.ingredient_prep)
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

    function buildInstructionRow(instruction) {
        instruction = instruction ?? ''

        if (!instruction) {
            return null
        }

        // const row_number = $ingredients_container.find('.instruction-row').length

        let $row = $('<div class="row instruction-row">')

        let $input = $('<input name="instruction[]" class="form-control" type="text" placeholder="Type what needs to happen at this step" required>').val(instruction)
        let $btn_remove = $('<button class="btn btn-sm btn-outline-error btn-instruction-remove">').append($('<i class="fa fa-remove">'))

        $row.append([
            $('<div class="col-lg-11 mb-3">').append($input),
            $('<div class="col-lg-1 mb-3">').append($btn_remove),
        ])

        return $row;
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
                const recipe_data = responses[0]

                if (typeof recipe_data.title === 'undefined') {
                    // new recipe
                    return;
                }

                $('[name=title]', $recipe_form).val(recipe_data.title)
                $('[name=course_id]', $recipe_form).val(recipe_data.course_id)
                $('[name=cuisine_id]', $recipe_form).val(recipe_data.cuisine_id)
                $('[name=recipe_type_id]', $recipe_form).val(recipe_data.type_id)
                $('[name=serving_measure_id]', $recipe_form).val(recipe_data.serving_measure_id)

                if (recipe_data.ingredients.length > 0) {
                    for (const ingredient of recipe_data.ingredients) {
                        let $row = buildIngredientRow(ingredient)
                        $ingredients_container.append($row)
                    }
                }

                if (recipe_data.instructions.length > 0) {
                    for (const instruction of recipe_data.instructions) {
                        let $row = buildInstructionRow(instruction)
                        $instructions_container.append($row)
                    }
                }

                $('#image-preview').attr('src', recipe_data.image_filename).show()
            })
    }

    init()
}
