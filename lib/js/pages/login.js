import $ from "jquery";
import axios from 'axios'

export default function() {

    function ValidateLogin() {
        let error = 0;
        let error_msg = "";
        //dbpc("ulen: " + $('#user_name').val().length );
        //dbpc("plen: " + $('#password').val().length );
        if ($('#username').val().length == 0) {
            error = 1;
            error_msg += "\nYou are missing the username";
        }
        if ($('#password').val().length == 0) {
            error = 1;
            error_msg += "\nYou are missing the password";
        }
        if (error == 1) {
            alert("You have errors" + error_msg + "");
            return false;
        }
        return true;
    }

    $('#login_form').fadeIn(800);

    $('#cmdLogin').click(function(evt) {
        if (ValidateLogin()) {
            $('#imgLoader').removeClass('hidden');
            $('#cmdLogin').addClass('hidden');

            let elogin = btoa($('#username').val() + ';' + $('#password').val());
            $('#imgLoader').removeClass('hidden');
            $('#cmdLogin').addClass('hidden');

            axios.post('/api/v1/auth/login.php', {
                'elogin': elogin,
                'rememberme': ($('input[name=rememberme]').is(':checked') ? 1 : 0)
            })
                .then(response => {
                    response = response.data;
                    console.log(response);

                    var new_url = $('#request_uri').val() || '';
                    if (new_url.length === 0) {
                        new_url = '/home/';
                    }

                    window.location = new_url;

                })
                .catch(error => {
                    console.error(error.response.data.message);
                    $('#imgLoader').addClass('hidden');
                    $('#cmdLogin').removeClass('hidden');
                    $('#logon_error').text(error.response.data.message);
                })

        } else {
            return false;
        }
        return true;
    });

    $('input[type=checkbox]').iCheck({
        checkboxClass: 'icheckbox_square-blue',
        increaseArea: '20%' // optional
    });

}
