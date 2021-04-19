"use strict";

/**
 * @handler - Used for switching src attribute during resize
 */
export function adaptImage() {
    const src = $(this).attr('src');
    $(this).attr('src', $(this).attr('data-adaptive'));
    $(this).attr('data-adaptive', src);
    $(this).toggleClass('adapted');
    $(this).closest('.slick-slider').slick('refresh');
}

/**
 * @handler - Retrieves an option list for relative select form elements
 * Applies to any form element (input, select, textarea) as an event handles with a defined set of parameters as data-* attributes:
 * - get: type of options to get (can coincide with the target);
 * - url: a URL of backend handler (mandatory, but can be left empty);
 * - [target]: name of the <select> element to embed the handler result. (optional if coincides with data-get);
 * - [filter]: the key, the result should be filtered by (optional);
 * - [value]: the value, attached to a filter (optional, used if only filter is defined);
 */
export function getOptions() {
    if (!('get' in this.dataset) || !('url' in this.dataset)) return;
    const _this = this,
        $select = $(_this).closest('form').find(`select[name="${_this.dataset.target || _this.dataset.get}"]`);
    $(_this).closest('.row').addClass('loading');
    $.ajax({
        url: this.dataset.url,
        data: {
            get: this.dataset.get,
            filter: this.dataset.filter ? {
                name: this.dataset.filter,
                value: this.value,
            } : null,
        },
        dataType: 'json',
        type: 'post',
        success: function (data) {
            if (typeof data === 'string') data = JSON.parse(data);
            $select.html(data.options.map(e => `<option value="${e.ID}" ${e.AUX || ''} ${e.selected || ''} ${e.disabled || ''}>${e.NAME}</option>`));
            $select.trigger('change');
        },
        complete: function () {
            $(_this).closest('.row').removeClass('loading');
        },
    })
}

/**
 * @procedure - Automatically checks chackbox inputs within an element
 * @param {object} element - DOM element, containing inputs
 * @returns {number} - timer id;
 */
export function autoChange(element) {
    if (!$(element).is(':hover')) {
        const $this = $(element),
            $inputs = $this.find('input');
        let ix = $inputs.index($inputs.filter(':checked'));
        if (ix < $inputs.length - 1) ix++;
        else ix = 0;
        $inputs.eq(ix).siblings('label').trigger('click');
    }
    return setTimeout(function () {
        window.loops[element.id] = autoChange(element);
    }, 3000);
}

/**
 * @procedure - Sets placeholders for failed to load images with set data-pl attribute
 */
export function autoPlaceholder() {
    const replacePL = () => {
        $('img[data-pl]').each(function () {
            this.src = 'https://via.placeholder.com/' + (this.attributes.src.value || 500);
        });
    };
    document.body.addEventListener('error', function () {
    }, true);
    replacePL();
}

/**
 * @function - Capitalises a given string
 * @param {string} str - string to capitalise
 * @returns {string} - capitalised string
 */
export function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * @procedure - Logs an item depending on window.debugMode
 * @param logItem - an item to log
 */
export function consoleLog(logItem) {
    if (window.debugMode) console.dir(logItem);
}

/**
 * @procedure - A native implementation of Lazy Load pattern is applied to media elements (img, video)
 */
export function deferLoadIMG() {
    $('img, video').each(function () {
        $(this).data('load-src', $(this).attr('src'));
        $(this).data('load-srcser', $(this).attr('srcset'));
        $(this).removeAttr('src');
        $(this).removeAttr('srcset');
        $(this).addClass('defload');
    });
    $(document).on('scroll', function (e) {
        $('.defload').each(function () {
            if ($(this).offset().top < $(document).scrollTop() + $(window).height() * 1.5) {
                $(this).attr('src', $(this).data('load-src'));
                $(this).attr('srcset', $(this).data('load-srcset'));
                $(this).removeClass('defload');
            }
        });
    });
}

/**
 * @function - Transforms an object to a url params string
 * @param {object} params
 * @returns {string}
 */
export function encodeGETParams(params) {
    return "?" + Object.entries(params).map(e => e.join('=')).join('&');
}

/**
 * @function - Formats a number provided as price to a thousand-separated format
 * @param {number|string} price
 * @param {string} [separator]
 * @returns {string}
 */
export function formatPrice(price, separator = ' ') {
    return price.toString().replace(/(\d)(?=(\d\d\d)+([^\d]|$))/g, `$1${separator}`);
}

/**
 * @function - Retrieves a UserAgent name and returns it
 * @param {string} [prefix] - prefix for the returned value
 * @returns {string} - User Agent name
 */
export function getBrowserName(prefix = '') {
    const ua = navigator.userAgent;
    let className;
    if (ua.search(/Chrome/) !== -1) className = `${prefix}google_chrome`;
    if (ua.search(/Firefox/) !== -1) className = `${prefix}firefox`;
    if (ua.search(/Opera/) !== -1) className = `${prefix}opera`;
    if (ua.search(/Safari/) !== -1) className = `${prefix}safari`;
    if (ua.search(/MSIE/) !== -1) className = `${prefix}internet_explorer`;
    return className
}

/**
 * @function - Returns a cookie value
 * @param {string} cname - cookie name
 * @returns {string} - cookie value
 */
export function getCookie(cname) {
    const name = cname + "=";
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1);
        if (c.indexOf(name) === 0) return c.substring(name.length, c.length);
    }
    return "";
}

/**
 * @function - Returns a counted item string with a appropriate declension
 * @param {int} count - count of entity
 * @param {array} endings - list of endings, dependign of count. minimum length must be 3;
 * @param {string} [base] - base string to prepend endings
 * @returns {string} - counted item
 */
export function getDeclension(count, endings = [], base = '') {
    if (endings.length < 3) {
        return base;
    }
    count = count.toString();
    if (count.length > 2) {
        count = +count.slice(-2);
    }
    if (count >= 10 && count <= 20) {
        return base + endings[2];
    } else {
        if (count >= 10) count %= 10;
        switch (+count) {
            case 1:
                return base + endings[0];
            case 2:
            case 3:
            case 4:
                return base + endings[1];
            default:
                return base + endings[2];
        }
    }
}

/**
 * @function - Sets window dimensions into window global variable and returns an array of them;
 * @param {string|object} container - selector or a DOM object of the container (default 'body');
 * @return {array} of dimensions, as: [windowWidth, windowHeight, isMobile (wWidth < 768px)];
 */
export function getDimentions(container = 'body') {
    window.wWidth = $(window).width();
    window.wHeight = $(window).height();
    window.isMobile = !!$(container).data("breakpoint") ?
        $(window).width() <= $(container).data("breakpoint") : $(window).width() < 768;
    return [window.wWidth, window.wHeight, window.isMobile,];
}

/**
 * @function - Wraps an error message into jQuery element, ready to embed
 * @param {string} message - a message to be wrapped
 * @return {jQuery} - jQuery element containing the message
 */
export function getErrorLog(message) {
    return $('<div></div>').addClass('error-log').text(message);
}

/**
 * @function - Returns width of the window vertical scrollbar
 * @return {number}
 */
export function getScrollbarWidth() {
    const div = document.createElement('div');
    div.style.overflowY = 'scroll';
    div.style.visibility = 'hidden';
    document.body.append(div);
    const sw = div.offsetWidth - div.clientWidth;
    div.remove();
    return sw;
}

/**
 * @function - returns a URL query param string generated by form submission
 * @param {jQuery} $submittable - a jQuery wrapped <form> element
 * @return {string} - if there is any param returns a params string, instead returns current hostname
 */
export function getQueryState($submittable) {
    const params = $submittable.find("input:not([data-query-excluded])").serialize();
    const query = params ? `?${params}` : false;
    return query || location.origin + location.pathname;
}

/**
 * @function - Returns a pseudo-random int withing defined range
 * @param {int} from - start range value (default 0)
 * @param {int} to - end range value (default 10)
 * @return {int}
 */
export function getRandomInt(from = 0, to = 10) {
    if (from > to) {
        return from;
    }
    return Math.floor(Math.random() * (to - from) + from);
}

/**
 * @procedure - Replaces each <img> src attribute relative url to its remote instance in case of loading error
 * @param {string} url - the url of remote host
 * @param {string|bool} condition - depending condition (default true)
 */
export function getRemoteImages(url, condition = true) {
    if (condition) {
        $('img').each(function () {
            $(this).attr('src', url + $(this).attr('src'));
        });
        $(document).on('error', 'img', function () {
            if (!$(this).hasClass('retried')) $(this).addClass('retried').attr('src', url + $(this).attr('src'));
            else console.error('Cannot retrieve image: ' + $(this).attr('src'));
        });
    }
}

/**
 * @function - Parses element's transform style property into an object
 * @param {object <HTMLElement>} element
 * @return {object}
 */
export function parseElementTransforms(element) {
    if (!element || !element.style.transform) return {};
    return element.style.transform.replace(', ', ',').split(' ').map(tf => tf.match(/[^ ,()]+/g)).reduce((a,c) => {
        a[c[0]] = c.length > 2 ? c.slice(1) : c[1];
        return a;
    }, {});
}

/**
 * @function - Returns a map (plain JS object) of set GET parameters
 * @return {object}
 */
export function parseGETParams() {
    const params = {};
    window.location.search.replace(/\?/g, "").split("&").forEach(e => {
        const param = e.split('=');
        params[param[0]] = param[1];
    });
    return params;
}

/**
 * @function - Transforms the formatted price string into a JS number value
 * @param {string} val - formatted price string
 * @return {number}
 */
export function priceToInt(val) {
    return parseInt(val.toString().replace(/ /g, ""));
}

/**
 * @procedure - Reloads the page or redirects to a defined URL after a certain amount of time
 * @param {int} time - delay time in seconds (default 0, means apply immediately)
 * @param {string} redirect = a url to redirect
 */
export function refreshPage(time = 0, redirect= '') {
    time *= 1000;
    if (!redirect) setTimeout(function () {
        location.reload();
    }, time);
    else setTimeout(function () {
        location.assign(redirect);
    }, time);
}

/**
 * @procedure - Scrolls to the provided JQuery element
 * @param {JQuery} $element - element
 * @param {int} duration
 * @param {function|null} callback
 */
export function scrollTo($element, duration = 600, callback = null) {
    if ($element.length && typeof duration === 'number') {
        $('html, body').animate({
            scrollTop: $element.offset().top,
        }, duration, 'swing', callback);
    }
}

/**
 * @procedure - Sets the cookie
 * @param {string} cname - cookie name
 * @param {*} cvalue - cookie value (preferred to be a string)
 * @param {int} exdays - amount of days to keep cookie (default 0, means to clear cookie right after the page is closed)
 */
export function setCookie(cname, cvalue, exdays = 0) {
    const d = new Date();
    d.setTime(d.getTime() + exdays * 24 * 60 * 60 * 1000);
    const expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

/**
 * @function Transforms CSS transforms object into a string
 * @param {object} transforms
 * @return {string}
 */
export function stringifyTransforms(transforms) {
    if (typeof transforms !== 'object') return '';
    return Object.entries(transforms).map(e => `${e[0]}(${Array.isArray(e[0]) ? e.join(',') : e[1]})`).join(' ');
}

/**
 * @handler - Hangles the GIF behaviour of an <img> element
 * <img> element should contain the data-src attribute, containing the GIF image url to be switched to;
 * @return {boolean} - result of the operation;
 */
export function toggleGif() {
    const $this = $(this).find('img');
    if (!$this.length || !$this.data('src')) return false;
    $this.on('load', function () {
        $(this).closest('.loading').removeClass('loading');
        $(this).off('load');
    });
    $(this).toggleClass('playing');
    $(this).addClass('loading');
    const tmpSrc = $this.attr('src');
    $this.attr('src', $this.attr('data-src'));
    $this.attr('data-src', tmpSrc);
    return true;
}

/**
 * @procedure - Throws over a result, obtained at the backend to a provided frontend handler
 * @param {string} response - response from the backend
 * @param {function} responseHandler - a function to handle the response
 */
export function transJS(response, responseHandler) {
    responseHandler(response);
}

/**
 * @function - Transliterates string from cyrilic to roman charset
 * @param {string} str - string in cyrilic
 * @param {string} spaceSeparator - a separator for spaces (default '_');
 * @param {string} remainSeparator - separator for other chars (default '-')
 * @return {string}
 */
export function translit(str, spaceSeparator = '_', remainSeparator = '-') {
    const rus = "абвгдеёжзийклмнопрстуфхцчшщъыьэюя";
    const chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    const tr = ['a', 'b', 'v', 'g', 'd', 'e', 'yo', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'ts', 'tch', 'sh', 'sch', '', 'i', '', 'e', 'yu', 'ya',];
    return str.toLowerCase().split('').map((e, i) => {
        if (e === ' ') return spaceSeparator;
        if (rus.includes(e)) return tr[rus.indexOf(e)];
        if (chars.includes(e)) return e;
        return remainSeparator;
    }).join('');
}

/**
 * @procedure - Truncates text to a defined lenght
 * @param {string|jQuery} $selector - selector string or jQuery element to apply
 * @param truncateLength - preferred length of inner text in chars (default - 30)
 */
export function truncateText($selector, truncateLength = 30) {
    if (!$selector) $selector = '.truncate';
    $selector = $($selector);
    $selector.each(function () {
        const length = +$(this).data('length') || truncateLength;
        let text = $(this).text().replace(/(\n|\s+|\t)/g, ' ');
        if (text.length - 3 > length) {
            $(this).text(text.slice(0, length) + '...');
            $(this).attr('title', text);
        }
    });
}
