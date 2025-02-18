import $ from "jquery";
import axios from 'axios'
import {DateTime} from 'luxon'
import Modal from 'bootstrap/js/src/modal'

export default function() {
    let $mdl_view = new Modal(document.getElementById('mdl-app-view'), {
        focus: false
    })

    const $frm = $('#frm-chef-app')
    const $approval_alert = $('#alert-approval')

    const $pending_container = $('#pending-container')
    const $search_container = $('#search-results')

    const $pending_tbody = $('tbody', $pending_container)
    const $search_tbody = $('tbody', $search_container)

    const $no_pending = $('#no-pending')
    const $no_search = $('#no-search')
    const $first_search = $('#first-search')

    const get_status_color = function(status_id) {
        let status_color = 'text-info'
        if (+status_id === 2) {
            status_color = 'text-success'
        } else if (+status_id === 3) {
            status_color = 'text-error'
        }
        return status_color
    }

    const build_app_row = function(app, from_search = false){
        let $tr = $('<tr class="chef-app-row align-middle">').data('app', app)

        let created = DateTime.fromSQL(app.date_created)
        let diff = DateTime.now().diff(created, 'days').toObject()

        let status_color = get_status_color(app.chef_application_status_id)

        $tr.append([
            $('<td>').text(app.name),
            $('<td>').text(app.email),
            $('<td>').text(app.relation_name),
            $('<td>').text(app.chef_application_status).addClass(status_color),
            $('<td>').text(created.toLocaleString(DateTime.DATETIME_SHORT) + ', ' + Math.round(diff.days) + ' days ago'),
            $('<td>').append(
                $('<button>').attr({
                    class: 'btn btn-sm ' + (from_search ? 'btn-outline-secondary btn-view-app' : 'btn-outline-primary btn-approve-app')
                }).html('View &nbsp;<i class="fa fa-external-link-square"></i>')
            )
        ])

        return $tr;
    }

    const loadFormInputs = function() {
        axios.get('/api/v1/chef/list.php')
            .then(response => {
                response = response.data;
                console.log(response);

                let $disconnected = $('<optgroup label="Not Linked to a User">')
                let $connected = $('<optgroup label="Already Linked">')

                for (const item of response.data) {
                    if (+item.is_linked_to_user === 1) {
                        $connected.append(
                            $('<option>').attr('value', item.id).text(item.name)
                        );
                    } else {
                        $disconnected.append(
                            $('<option>').attr('value', item.id).text(item.name)
                        );
                    }
                }

                $('#chef_id').append([$disconnected, $connected])
            })

        axios.get('/api/v1/account/user/list.php')
            .then(response => {
                response = response.data;
                console.log(response);

                for (const item of response.data) {
                    $('#user_id').append(
                        $('<option>').attr('value', item.id).text(item.name)
                    );
                }
            })
    };

    function loadPending() {
        $pending_container.disable()

        axios.get('/api/v1/chef/application/list.php?chef_application_status_id=1')
            .then(response => {
                response = response.data;
                console.log(response);

                $pending_container.enable()
                $pending_tbody.empty()

                if (response.data.length === 0) {
                    $no_pending.show()
                    return;
                }

                $no_pending.hide()
                let $tbody = $pending_container.find('tbody')

                for (let app of response.data) {
                    $tbody.append(build_app_row(app))
                }
            })
    }

    function loadSearch(first = false) {
        if (first) {
            $search_tbody.empty()
            $first_search.show()
            return
        }

        $search_container.disable()

        let payload = {
            user_id: $('#user_id').val(),
            chef_application_status_id: $('#status_id').val(),
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val(),
        }

        axios.get('/api/v1/chef/application/list.php?' + $.param(payload))
            .then(response => {
                response = response.data;
                console.log(response);

                $search_container.enable()
                $search_tbody.empty()
                $first_search.hide()

                if (response.data.length === 0) {
                    $no_search.show()
                    return;
                }

                $no_search.hide()
                let $tbody = $search_container.find('tbody')

                for (let app of response.data) {
                    $tbody.append(build_app_row(app, true))
                }
            })
    }

    const load_app_modal = function(app, with_approval = true) {
        let created = DateTime.fromSQL(app.date_created)

        $('.modal .submitted-by').text(app.name)
        $('.modal .submitted-on').text(created.toLocaleString(DateTime.DATETIME_SHORT))

        let status_color = get_status_color(app.chef_application_status_id)
        $('.modal .status').text(app.chef_application_status)
            .removeClass('text-success text-error')
            .addClass(status_color)


        $('[name=chef_application_id]', $frm).val(app.id)
        $('[name=relationship]', $frm).val(app.relationship)
        $('[name=story]', $frm).val(app.story)

        if (+app.already_exists === 1) {
            $('[name=already_exists][value=1]', $frm).prop('checked', true)
        } else {
            $('[name=already_exists][value=0]', $frm).prop('checked', true)
        }

        $('[name=approval_status]', $frm).prop('checked', false)
        $('[name=chef_id]', $frm).val('')

        if (with_approval) {
            $('.div-app-approval-tools', $frm).show()
            $('[type=submit]', $frm).show()
        } else {
            $('.div-app-approval-tools', $frm).hide()
            $('[type=submit]', $frm).hide()
        }

        $approval_alert.hide()
        $mdl_view.show()
    }

    function init() {
        loadFormInputs()
        loadPending()
        loadSearch(true)

        $(document).on('click', '.btn-approve-app', function(e){
            const $btn = $(e.currentTarget)
            const app = $btn.closest('tr').data('app')
            console.log(app)
            load_app_modal(app)
        })

        $(document).on('click', '.btn-view-app', function(e){
            const $btn = $(e.currentTarget)
            const app = $btn.closest('tr').data('app')
            console.log(app)
            load_app_modal(app, false)
        })

        $('[name=approval_status]', $frm).change(function(){
            const approved = $('#status-approve').prop('checked')
            if (approved) {
                $('.chef-link-container').fadeIn()
                $('#chef_id').val('')
            }
            else {
                $('.chef-link-container').hide()
                $('#chef_id').val(0)
            }
        })

        $frm.submit(function(e){
            e.preventDefault()

            if ($('[type="submit"]', $frm).hasClass('disabled')) {
                return false;
            }

            let data = $frm.serializeObject();
            console.log(data)

            let payload = {
                action: 'approve',
                chef_application_id: +data.chef_application_id,
                status: +data.approval_status === 1,
                chef_id: 0
            }

            if (payload.status) {
                payload.chef_id = +data.chef_id
            }

            $approval_alert.disable()

            $('[type="submit"]', $frm)
                .addClass('disabled')
                .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

            axios.patch('/api/v1/chef/application/', payload)
                .then(response => {
                    response = response.data;
                    console.log(response);
                    $mdl_view.hide();
                    loadPending();

                })
                .catch(error => {

                    $('[type="submit"]', $frm)
                        .removeClass('disabled')
                        .html('Submit')

                    console.error(error.response.data.message);
                    $approval_alert.removeClass((i, c) => (c.match(/alert-/g) || []).join(' '))
                        .addClass('alert-error')
                        .text(error.response.data.message)
                        .enable()
                })

            return false
        })

        $('#btn-app-search').click(function(){
            loadSearch()
        })
    }

    init()
}
