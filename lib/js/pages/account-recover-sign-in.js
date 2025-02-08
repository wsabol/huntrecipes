import $ from "jquery";
import axios from 'axios'
import DigitInput from './../widgets/DigitInput'

export default function() {
    const $alert = $('#alert-sign-in')
    const $form = $('#frm-user-sign-in')

    let code_input = new DigitInput('#otp-input', {
        num_of_digits: 6,
        required: true
    })

    $form.submit(function(){

        if ($('[type="submit"]', $form).hasClass('disabled')) {
            return false;
        }

        $('[type="submit"]', $form)
            .addClass('disabled')
            .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

        code_input.disable()

        const code = code_input.val()
        let elogin = btoa($('[name="email"]', $form).val() + ';' + code);

        axios.post('/api/v1/auth/otp-sign-in.php', {
            elogin: elogin,
        })
            .then(response => {
                response = response.data;
                console.log(response);

                window.location = '/home/';

            })
            .catch(error => {

                $('[type="submit"]', $form)
                    .removeClass('disabled')
                    .html('Sign in')

                code_input.enable()

                $alert.removeClass((i, name) => (name.match(/alert-/g) || []).join(' '))
                    .addClass('alert-error')
                    .text(error.response.data.message)

                console.error(error.response.data.message);
            })

        return false
    })
}
