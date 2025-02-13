import $ from "jquery";
import axios from 'axios'

export default function() {
    const $form = $('#frm-reset-pwd')
    const $reset_alert = $('#alert-reset-pwd')

    // Add custom validation for password matching
    const password = document.getElementById("password");
    const confirm = document.getElementById("confirm-password");

    function validatePassword() {
        $form.addClass('was-validated')

        if (password.value !== confirm.value) {
            confirm.setCustomValidity("Passwords do not match");
            confirm.classList.add("is-invalid");
        } else {
            confirm.setCustomValidity('');
            confirm.classList.remove("is-invalid");
        }
    }

    password.onchange = validatePassword;
    confirm.onkeyup = validatePassword;

    $form.submit(function(e){
        e.preventDefault()

        this.classList.add('was-validated')
        if (!this.checkValidity()) {
            return false;
        }

        if (password.classList.contains('is-invalid') || $(password).is(':invalid')) {
            return false;
        }

        if (confirm.classList.contains('is-invalid') || $(confirm).is(':invalid')) {
            return false;
        }

        if ($('[type="submit"]', $form).hasClass('disabled')) {
            return false;
        }

        let payload = $form.serializeObject();
        $reset_alert.disable();

        $('[type="submit"]', $form)
            .addClass('disabled')
            .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

        axios.patch('/api/v1/account/user/', {
            action: 'reset-pwd',
            password: btoa(payload.password),
            user_id: $('[name=user_id]', $form)
        })
            .then(response => {
                response = response.data;
                console.log(response);

                $form.remove();
                $reset_alert.removeClass('alert-error')
                    .addClass('alert-success')
                    .text('Nice Work! Your password have been successfully changed. Happy cooking!')
                    .enable()

            })
            .catch(error => {

                $('[type="submit"]', $form)
                    .removeClass('disabled')
                    .html('Submit New Password')

                debugger
                $reset_alert.removeClass('alert-success')
                    .addClass('alert-error')
                    .text(error.response.data.message)
                    .enable()

                console.error(error.response.data.message);
            })

        return false
    })
}
