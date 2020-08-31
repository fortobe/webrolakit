"use strict";

export function adaptImage() {
    const src = $(this).attr('src');
    $(this).attr('src', $(this).attr('data-adaptive'));
    $(this).attr('data-adaptive', src);
    $(this).toggleClass('adapted');
    $(this).closest('.slick-slider').slick('refresh');
}

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
        window.loops [element.id] = autoChange(element);
    }, 3000);
}

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

export function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

export function consoleLog(logItem) {
    if (window.debugMode) console.dir(logItem);
}

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

export function encodeGETParams(params) {
    return "?" + Object.entries(params).map(e => e.join('=')).join('&');
}

export function formatPrice(price = 0) {
    return price.toString().replace(/(\d)(?=(\d\d\d)+([^\d]|$))/g, '$1 ');
}

export function getBrowserName() {
    const ua = navigator.userAgent;
    let className;
    if (ua.search(/Chrome/) !== -1) className = 'br_google_chrome';
    if (ua.search(/Firefox/) !== -1) className = 'br_firefox';
    if (ua.search(/Opera/) !== -1) className = 'br_opera';
    if (ua.search(/Safari/) !== -1) className = 'br_safari';
    if (ua.search(/MSIE/) !== -1) className = 'br_internet_explorer';
    return className
}

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

export function getDeclension(count, base = '', endings = []) {
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

export function getDimentions() {
    window.wWidth = $(window).width();
    window.wHeight = $(window).height();
    window.isMobile = ($("body").data("breakpoint")) ?
        $(window).width() <= $("body").data("breakpoint") : $(window).width() < 768;
}

export function getErrorLog(message) {
    return $('<div></div>').addClass('error-log').text(message);
}

export function getScrollbarWidth() {
    const div = document.createElement('div');
    div.style.overflowY = 'scroll';
    div.style.visibility = 'hidden';
    document.body.append(div);
    const sw = div.offsetWidth - div.clientWidth;
    div.remove();
    return sw;
}

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
            $select.html(data.options.map(e => `<option value="${e.ID}" ${e.selected} ${e.disabled}>${e.NAME}</option>`));
            $select.trigger('change');
        },
        complete: function () {
            $(_this).closest('.row').removeClass('loading');
        }
    })
}

export function getRandomInt(from, to) {
    from = from || 0;
    to = to || 10;
    if (from > to) {
        return from;
    }
    return Math.floor(Math.random() * (to - from) + from);
}

export function getRemoteImages(url, condition) {
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

export function parseGETParams() {
    const params = {};
    window.location.search.replace(/\?/g, "").split("&").forEach(e => {
        const param = e.split('=');
        params[param[0]] = param[1];
    });
    return params;
}

export function priceToInt(val) {
    return parseInt(val.toString().replace(/ /g, ""));
}

export function refreshPage(time, redirect) {
    time *= 1000;
    if (!redirect) setTimeout(function () {
        location.reload();
    }, time);
    else setTimeout(function () {
        location.assign(redirect);
    }, time);
}

export function setCookie(cname, cvalue, exdays) {
    const d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    const expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

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
}

export function transJS(response, responseHandler) {
    eval('const data = ' + response + ";");
    responseHandler(data);
}

export function translit(str) {
    const rus = "абвгдеёжзийклмнопрстуфхцчшщъыьэюя";
    const tr = ['a', 'b', 'v', 'g', 'd', 'e', 'yo', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'ts', 'tch', 'sh', 'sch', '', 'i', '', 'e', 'yu', 'ya'];
    return str.toLowerCase().split('').map((e, i) => {
        if (e === ' ') return '_';
        if (rus.includes(e)) return tr[rus.indexOf(e)];
        if (e.includes(e)) return e;
        return '-';
    }).join('');
}

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
