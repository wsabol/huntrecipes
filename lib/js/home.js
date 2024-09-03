import HuntRecipes from './HuntRecipes';
import axios from 'axios'
import $ from "jquery";
import {DateTime} from 'luxon';

export default function() {

    function loadFinancialStatement() {
        const this_year = HuntRecipes.fyear();
        const last_year = HuntRecipes.fyear() - 1;
        let assets;
        let debt;
        let cash_flows;

        let get_assets = axios.get('/api/v1/balance-sheet/?' + $.param({
            section: 'assets',
            fyear: this_year,
        }))
            .then(response => {
                response = response.data;
                console.log(response);

                assets = response.data.assets;

                $('.balance-sheet-cash').text(formatMoney(assets.current_assets.cash))
                $('.balance-sheet-securities').text(formatMoney(0))
                $('.balance-sheet-ar').text(formatMoney(assets.current_assets.accounts_receivable))

                const retirement = assets.noncurrent_assets.investments.retirement;
                $('.balance-sheet-invest-ira').text(formatMoney(retirement))
                $('.balance-sheet-invest-other').text(formatMoney(assets.noncurrent_assets.investments.total - retirement))

                const real_estate = assets.noncurrent_assets.real_estate;
                $('.balance-sheet-re-homestead').text(formatMoney(real_estate.homestead))
                $('.balance-sheet-re-other').text(formatMoney(real_estate.total - real_estate.homestead))

                const fixed = assets.noncurrent_assets.fixed;
                $('.balance-sheet-fixed-art').text(formatMoney(fixed.art))
                $('.balance-sheet-fixed-jewelry').text(formatMoney(fixed.jewelry))
                $('.balance-sheet-fixed-vehicles').text(formatMoney(fixed.vehicles))
                $('.balance-sheet-fixed-other').text(formatMoney(fixed.total - (fixed.art + fixed.jewelry + fixed.vehicles)))

                // total assets
                $('.balance-sheet-total-assets').text(formatMoney(assets.total));

            })
            .catch(error => {
                console.error(error.response.data.message);
                alert('Something went wrong: ' + error.response.data.message)
            })

        let get_debt = axios.get('/api/v1/balance-sheet/?' + $.param({
            section: 'debt',
            fyear: this_year,
        }))
            .then(response => {
                response = response.data;
                console.log(response);

                debt = response.data.debt;

                const np = debt.notes_payable;
                $('.balance-sheet-np-mortgage').text(formatMoney(np.mortgage))
                $('.balance-sheet-np-other').text(formatMoney(np.total - np.mortgage))

                const ap = debt.accounts_payable;
                $('.balance-sheet-ap-credit').text(formatMoney(ap.credit_cards))
                $('.balance-sheet-ap-other').text(formatMoney(ap.total - ap.credit_cards))

                $('.balance-sheet-debt-other').text(formatMoney(0))
                $('.balance-sheet-debt-total').text(formatMoney(debt.total))

            })
            .catch(error => {
                console.error(error.response.data.message);
                alert('Something went wrong: ' + error.response.data.message)
            })

        // cash flows this year
        let cash_flow_this_year = axios.get('/api/v1/balance-sheet/?' + $.param({
            section: 'cash_flows',
            fyear: this_year,
        }))
            .then(response => {
                response = response.data;
                console.log(response);

                cash_flows = response.data.cash_flows;
                writeCashFlow(cash_flows, 'current')

            })
            .catch(error => {
                console.error(error.response.data.message);
                alert('Something went wrong: ' + error.response.data.message)
            })

        // cash flows prev year
        let cash_flow_prev_year = axios.get('/api/v1/balance-sheet/?' + $.param({
            section: 'cash_flows',
            fyear: last_year,
        }))
            .then(response => {
                response = response.data;
                console.log(response);

                writeCashFlow(response.data.cash_flows, 'prev')

            })
            .catch(error => {
                console.error(error.response.data.message);
                alert('Something went wrong: ' + error.response.data.message)
            })

        Promise.all([get_assets, get_debt])
            .then(() => {
                const net_worth = assets.total - debt.total;
                $('.balance-sheet-net-worth').text(formatMoney(net_worth))
            })
    }

    function writeCashFlow(cash_flows, type) {
        const sources = cash_flows.sources;
        $('.balance-sheet-sources-' + type + '-salary').text(formatMoney(sources.gross_salary))
        $('.balance-sheet-sources-' + type + '-freelance').text(formatMoney(sources.freelance))
        $('.balance-sheet-sources-' + type + '-interest').text(formatMoney(sources.interest_dividends))
        $('.balance-sheet-sources-' + type + '-distributions').text(formatMoney(sources.llc_distributions))
        $('.balance-sheet-sources-' + type + '-sales').text(formatMoney(sources.sale_of_assets))
        $('.balance-sheet-sources-' + type + '-gigs').text(formatMoney(sources.gigs))
        $('.balance-sheet-sources-' + type + '-other').text(formatMoney(sources.rents + sources.tax_refund + sources.payouts + sources.other))
        $('.balance-sheet-sources-' + type + '-total').text(formatMoney(sources.total))

        const uses = cash_flows.uses;
        $('.balance-sheet-uses-' + type + '-living').text(formatMoney(uses.living_expenses))
        $('.balance-sheet-uses-' + type + '-mortgage').text(formatMoney(uses.mortgage_loan_payments))
        $('.balance-sheet-uses-' + type + '-loans').text(formatMoney(uses.other_loan_payments))
        $('.balance-sheet-uses-' + type + '-income-tax').text(formatMoney(uses.income_taxes))
        $('.balance-sheet-uses-' + type + '-contribution').text(formatMoney(uses.investment_contributions))
        $('.balance-sheet-uses-' + type + '-other-tax').text(formatMoney(uses.other_taxes))
        $('.balance-sheet-uses-' + type + '-other').text(formatMoney(uses.investment_fees + uses.work_expenses))
        $('.balance-sheet-uses-' + type + '-total').text(formatMoney(uses.total))
        $('.balance-sheet-net-cash-flow-' + type).text(formatMoney(cash_flows.net_cash_flow))
    }

    function loadBillFormInputs($form) {
        axios.get('/api/v1/settings/user/list.php')
            .then(response => {
                response = response.data;
                console.log(response);

                for (const user of response.data) {
                    $('#login_id', $form).append(
                        $('<option>').attr('value', user.id).text(user.name)
                    );
                }
            });
    }

    const setNothingDue = function(bill_id, nothing_due, nmonth, fyear, $modal) {
        axios.patch('/api/v1/expenses/bill/', {
            action: 'nothing-due',
            nothing_due: !!nothing_due,
            bill_id: bill_id,
            fyear: fyear,
            nmonth: nmonth
        })
            .then(response => {
                response = response.data;
                console.log(response);

                $('#tblMissingBills').dataTable().api().ajax.reload(null, false);
                $modal.modal('hide');
            })
            .catch(error => {
                console.error(error.response.data.message);
                alert('Something went wrong: ' + error.response.data.message)
            })
    }

    function initMissingBills() {
        const $modal = $('#modalBillPayment');
        const $form = $('#frmBillPaymentInput');
        const $table = $('#tblMissingBills');

        $table.dataTable({
            paging: false,
            searching: false,
            info: false,
            ajax: '/api/v1/expenses/bill/payment/missing-payments.php?' + $.param({
                fyear: HuntRecipes.fyear()
            }),
            order: [
                [1, 'asc'],
                [0, 'asc']
            ],
            language: {
                loadingRecords: 'Loading...',
                emptyTable: 'No missing bills found'
            },
            columns: [{
                title: 'Bill',
                data: 'name',
            }, {
                title: 'Cycle',
                data: 'month_name',
                render: function(data, type, bill) {
                    if (type === 'display') {
                        return $('<span>').append([
                            bill.month_name + ' ' + bill.fyear + ' ',
                            $('<a>').addClass("btn btn-default btn-xs")
                                .html('<i class="fa fa-external-link-square"></i>')
                                .attr({
                                    href: '/expenses/bill-pay-checklist/?nmonth=' + bill.nmonth,
                                })
                        ]).html()
                    }
                    else if (type === 'sort') {
                        return bill.fyear + '-' + String(bill.nmonth).padStart(2, '0');
                    }
                    return data;
                }
            }, {
                title: '',
                data: 'id',
                class: 'text-right',
                render: function(data, type) {
                    if (type === 'display') {
                        return $('<span>').append([
                            $('<button>').addClass("btn btn-primary btn-xs btnMakePayment")
                                .css({ marginTop: '-3px'})
                                .attr("role", "button")
                                .text('Make Payment'),
                        ]).html()
                    }
                    return data;
                }
            }],
            data: []
        })

        $table.on('preXhr.dt', function() {
            $table.find('caption').fadeTo(300, 0.5);
        });

        $table.on('xhr.dt', function(e, settings, json, xhr) {
            let $caption = $table.find('caption');
            if (json.data.length === 0) {
                if ($caption.length > 0) {
                    $caption.fadeTo(300, 1);
                } else {
                    $table.append($('<caption>').text("All caught up!").addClass("callout callout-success"))
                }
            } else {
                $caption.remove();
            }
        });

        $table.on('click', '.btnMakePayment', function(e){
            const $this = $(e.currentTarget)
            const bill_data = $table.dataTable().api().row($this.closest('tr')[0]).data();

            // console.log(bill_data);
            $('#bill_id', $form).val(bill_data.id);
            $('#bill_payment_id', $form).val(0);
            $('#amount', $form).val('');
            $('#date_due', $form).val(bill_data.date_due);
            $('#date_paid', $form).val(bill_data.date_due);
            $('#login_id', $form).val(HuntRecipes.current_user_id());
            $('#notes', $form).val('');
            $('[name=fyear]', $form).val(bill_data.fyear);
            $('[name=nmonth]', $form).val(bill_data.nmonth);
            $('.bill-title', $modal).text(bill_data.name + ' ' + bill_data.month_name + '/' + bill_data.fyear)
            $modal.modal('show');

            $modal.find('.spnPreviousPayment')
                .empty()
                .append([
                    $('<i class="fa fa-info-circle">'),
                    ' No payments yet'
                ])
                .show()

            axios.get('/api/v1/expenses/bill/payment/previous-payment.php?' + $.param({
                bill_id: bill_data.id,
                date: bill_data.date_due
            }))
                .then(response => {
                    let data = response.data.data

                    if (!!data) {
                        $modal.find('.spnPreviousPayment')
                            .empty()
                            .append([
                                $('<i class="fa fa-info-circle">'),
                                ' Last payment was ' + formatMoney(data.amount) + ' on ' + DateTime.fromSQL(data.date_paid).toFormat('M/d/y')
                            ])
                    }
                })
        });

        $('#btnNothingDue').click(function() {
            setNothingDue(
                $('#bill_id', $form).val(),
                true,
                $('[name=nmonth]', $form).val(),
                $('[name=fyear]', $form).val(),
                $modal
            );
        });

        $form.submit(function(evt) {
            evt.preventDefault();
            let payload = $form.serializeObject();
            payload.amount = parseMoney(payload.amount);

            axios.post('/api/v1/expenses/bill/payment/', payload)
                .then(response => {
                    response = response.data;
                    console.log(response);

                    $table.dataTable().api().ajax.reload(null, false);
                    $modal.modal('hide');
                })
                .catch(error => {
                    console.error(error.response.data.message);
                    alert('Something went wrong: ' + error.response.data.message)
                })

            return false;
        });

        $('#btnRefreshMissingPayments').click(function() {
            const settings = $table.dataTable().api().init();
            $table.dataTable().api().destroy();
            $table.dataTable(settings);
        });

        loadBillFormInputs($form);
    }

    function initYearTodo() {
        const $table = $('#tblYearTasks');

        $table.dataTable({
            paging: false,
            searching: false,
            info: false,
            ajax: '/api/v1/balance-sheet/end-of-year-todo.php?' + $.param({
                fyear: HuntRecipes.fyear()
            }),
            // order: [
            //     [1, 'asc'],
            //     [0, 'asc']
            // ],
            language: {
                loadingRecords: 'Loading...',
                emptyTable: 'No tasks found'
            },
            columns: [{
                title: 'Task',
                data: 'name',
            }, {
                title: 'Year',
                data: 'fyear',
            }, {
                title: 'Task',
                data: 'task',
            }, {
                title: 'Action',
                class: 'text-right',
                data: 'id',
                render: function(data, type, task) {
                    if (type === 'display') {
                        return $('<span>').append([
                            $('<a>').addClass("btn btn-primary btn-xs")
                                .html('Fix it <i class="fa fa-external-link-square"></i>')
                                .attr({
                                    href: task.link,
                                })
                        ]).html()
                    }
                    return data;
                }
            }],
            data: []
        })

        $table.on('preXhr.dt', function() {
            $table.find('caption').fadeTo(300, 0.5);
        });

        $table.on('xhr.dt', function(e, settings, json, xhr) {
            let $caption = $table.find('caption');
            if (json.data.length === 0) {
                if ($caption.length > 0) {
                    $caption.fadeTo(300, 1);
                } else {
                    $table.append($('<caption>').text("All caught up!").addClass("callout callout-success"))
                }
            } else {
                $caption.remove();
            }
        });

        $('#btnRefreshYearTodo').click(function() {
            const settings = $table.dataTable().api().init();
            $table.dataTable().api().destroy();
            $table.dataTable(settings);
        });
    }

    loadFinancialStatement();
    initMissingBills();
    initYearTodo();
}
