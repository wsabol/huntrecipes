import $ from "jquery";

(function() {
    'use strict'

    const currentQueryParams = () => Object.fromEntries(new URLSearchParams(location.search))

    window.currentQueryParams = currentQueryParams;

    $.fn.serializeObject = function() {
        let o = {};
        let a = this.serializeArray();
        $.each(a, function() {
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

})()
