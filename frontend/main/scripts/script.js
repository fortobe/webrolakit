"use strict";

import * as wrk from '../../common/scripts/wrk/'

$(function () {
    wrk.initPlugins();

    $('.accordeon .cont').on('transitionend', function () {
        $('.wrk.parallax').trigger('resize');
    });

    $(document).on('click', '[href="#"]', function (e) {
        e.preventDefault();
        alert('Functionality is unavailable in demo mode.');
    });
});
