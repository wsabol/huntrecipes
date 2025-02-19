import $ from "jquery";

(function() {
    'use strict'

    const formatMoney = function(value, c, d, t) {
        if (isNaN(value) || value === null || value === false) return '$--';
        c = isNaN(c = Math.abs(c)) ? 2 : c;
        d = d === undefined ? "." : d;
        t = t === undefined ? "," : t;
        let s = value < 0 ? "-" : "";
        let i = String(parseInt(value = Math.abs(Number(value) || 0).toFixed(c)));
        var j = (j = i.length) > 3 ? j % 3 : 0;
        return s + '$' + (j ? i.substring(0, j) + t : "") + i.substring(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(value - i).toFixed(c).slice(2) : "");
    }

    const parseMoney = function(value) {
        if (typeof value === 'string') {
            value = value.replace(/,/g, '').replace(/[$]/g, '');
        }
        return parseFloat(value);
    }

    const formatPerc = function(value, c, d, t) {
        if (isNaN(value) || value === null || value === false) return '--%';
        if (value === Infinity) {
            return 'Inf%';
        }
        if (value === -Infinity) {
            return '-Inf%';
        }
        c = isNaN(c = Math.abs(c)) ? 2 : c;
        d = d === undefined ? "." : d;
        t = t === undefined ? "," : t;
        value *= 100;
        let s = value < 0 ? "-" : "";
        let i = String(parseInt(value = Math.abs(Number(value) || 0).toFixed(c)));
        var j = (j = i.length) > 3 ? j % 3 : 0;
        return s + (j ? i.substring(0, j) + t : "") + i.substring(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(value - i).toFixed(c).slice(2) : "") + '%';
    }

    const parsePerc = function(value) {
        if (typeof value === 'string') {
            value = value.replace(/,/g, '').replace(/[%]/g, '');
        }
        return parseFloat(value) / 100;
    }

    const get_caret = function(value) {
        if (value > 0) return 'fa-caret-up';
        if (value < 0) return 'fa-caret-down';
        return 'fa-caret-left';
    }
    const get_class = function(value) {
        if (value > 0) return 'text-green';
        if (value < 0) return 'text-red';
        return 'text-muted';
    }
    const get_incr_perc = function(a, b) {
        if ((a - b) === 0 && b === 0) return null;
        if (b === 0) return Infinity;
        return (a - b) / Math.abs(b);
    }

    const currentQueryParams = () => Object.fromEntries(new URLSearchParams(location.search))

    window.formatMoney = formatMoney;
    window.parseMoney = parseMoney;
    window.formatPerc = formatPerc;
    window.parsePerc = parsePerc;

    window.get_caret = get_caret;
    window.get_class = get_class;
    window.get_incr_perc = get_incr_perc;

    window.currentQueryParams = currentQueryParams;

    $.fn.serializeObject = function() {
        let o = {};
        let a = this.serializeArray();
        jQuery.each(a, function() {
            if (o[this.name]) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    }

    // Enable function - sets opacity to 1 and shows if hidden
    $.fn.enable = function(duration = 0) {
        return this.each(function() {
            const $element = $(this);
            $element.css('pointer-events', '')

            // If element is hidden, show it
            if ($element.is(':hidden')) {
                $element.show();
            }

            // Animate to full opacity
            $element.animate({
                opacity: 1
            }, duration);
        });
    };

    // Disable function - sets opacity to 0.5 if visible
    $.fn.disable = function(duration = 0) {
        return this.each(function() {
            const $element = $(this);

            // Only modify opacity if element is visible
            if ($element.is(':visible')) {
                $element.css('pointer-events', 'none')
                $element.animate({
                    opacity: 0.5
                }, duration);
            }
        });
    };

    Array.prototype.shuffle = function() {
        for (let i = this.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [this[i], this[j]] = [this[j], this[i]];
        }
    }

    Array.prototype.sum = function(){
        return this.reduce((sum, x) => sum + x)
    }

    Array.prototype.mean = function(){
        return this.sum() / this.length
    }

})()
