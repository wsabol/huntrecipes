import $ from "jquery";

class DigitInput {
    constructor(selector, options = {}) {
        this.options = options ?? {};
        this.options.num_of_digits = +options.num_of_digits ?? 6;
        this.options.required = !!options.required ?? false;

        const $container = $(selector)
        this.$container = $container

        $container.empty();
        $container.addClass('digit-group');

        for (let i = 0; i < options.num_of_digits; i++) {
            let $input = $('<input>').attr({
                type: 'text',
                class: 'digit-input',
                maxlength: 1,
                'data-index': i
            })
                .prop('required', options.required)

            $input.appendTo($container);
        }

        const $inputs = $('.digit-input', $container);

        // Only allow numbers
        $inputs.on('input', function(e) {
            const value = this.value
            this.value = value.replace(/[^0-9]/g, '');
        });

        // Handle input and autofocus
        $inputs.on('input', function(e) {
            const $currentInput = $(this);
            const currentIndex = parseInt($currentInput.data('index'));

            if ($currentInput.val() && currentIndex < 5) {
                $inputs.eq(currentIndex + 1).focus();
            }
        });

        // Handle backspace
        $inputs.on('keydown', function(e) {
            const $currentInput = $(this);
            const currentIndex = parseInt($currentInput.data('index'));

            if (e.key === 'Backspace') {
                if (!$currentInput.val() && currentIndex > 0) {
                    e.preventDefault();
                    $inputs.eq(currentIndex - 1).focus().val('');
                }
            }
        });

        let self = this;

        // Handle paste event
        $inputs.on('paste', function(e) {
            e.preventDefault();

            // Get pasted content
            const pastedText = (e.originalEvent.clipboardData || window.clipboardData)
                .getData('text')
                .replace(/[^0-9]/g, '')  // Remove non-numeric characters
                .slice(0, self.options.num_of_digits);

            self.val(pastedText)
        });
    }

    val(value){
        const $inputs = $('.digit-input', this.$container)

        if (typeof value === 'undefined' && arguments.length === 0) {
            return Array.from($inputs)
                .map(input => input.value)
                .join('');
        }

        value = String(value).slice(0, this.options.num_of_digits)

        // Distribute digits across inputs
        $inputs.each(function(index) {
            if (index < value.length) {
                this.value = value[index];
            }
        });

        // Focus the next empty input or the last input
        const nextEmptyIndex = Array.from($inputs).findIndex(input => !input.value);
        if (nextEmptyIndex !== -1) {
            $inputs.eq(nextEmptyIndex).focus();
        } else {
            $inputs.eq(5).focus();
        }
    }

    disable() {
        Array.from($('.digit-input', this.$container))
            .map(input => $(input).prop('disabled', true))
    }

    enable() {
        Array.from($('.digit-input', this.$container))
            .map(input => $(input).prop('disabled', false))
    }
}

export default DigitInput;
