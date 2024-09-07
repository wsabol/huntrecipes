import $ from "jquery";

export default (function() {
    const current_user_id = +$('body').data('current-user-id') || 0

    return {
        current_user_id: () => current_user_id,

        init() {

            /*
            init checkbox styling
             */
            $('input[type=checkbox]').iCheck({
                checkboxClass: 'icheckbox_square-blue',
                radioClass: 'iradio_square-blue',
                increaseArea: '20%' // optional
            });

            $('input[type=radio]').iCheck({
                checkboxClass: 'icheckbox_square-red',
                radioClass: 'iradio_square-red',
                increaseArea: '20%' // optional
            });
        },
    }
})()
