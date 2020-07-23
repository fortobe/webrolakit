'use strict';
import {
    getRandomInt,
} from "../functions";

//accordeon
export function initAccordeonPlugin($selector = $(".wrk.accordeon, .wrk-accordeon")) {
    if ($selector.length > 0) {

        $selector.filter('.static').find('.cont').each(function () {
            $(this).css('height', $(this).hasClass('active') ? this.offsetHeight : this.scrollHeight);
        });

        $(document).on("click", ".trigger, .wrk-trigger", function (e) {
            e.preventDefault();
            const $this = $(this),
                $parent = $this.closest(".accordeon, .wrk-accordeon"),
                $target = $($this.data('target') || $this.attr('href'));
            if (!$target.length) return;
            if ($parent.hasClass("excluding")) {
                $parent.find('.cont.active, .trigger.active').removeClass('active');
                $($this).addClass('active');
                $($target).addClass('active');
            } else {
                $($this).toggleClass('active');
                $($target).toggleClass('active');
            }
        });
    }
}

//ajax-forms
export function initAjaxFormsPlugin(ajaxFormHandler, selector = '.wrk.ajax-form', urlPrefix = "", urlPostfix = "") {
    if ($(selector).length > 0) {
        $(document).on('submit', selector, function (e) {
            e.preventDefault();
            const $this = $(this),
                $button = $this.find('[type="submit"]'),
                data = $this.data(),
                formData = $this.attr("enctype") ? new FormData(this) : $this.serialize(),
                dataType = data.datatype || "json",
                method = $this.attr("method") || 'post',
                options = {
                    data: formData,
                    dataType: dataType,
                    method: method,
                    async: !!data.async,
                    success: function (result) {
                        if (!!data.handler && typeof window[data.handler] === 'function') {
                            window[data.handler](result, $this);
                        } else if (typeof ajaxFormHandler === 'function') {
                            ajaxFormHandler(result, $this);
                        } else {
                            if (result.success) {
                                const $message = $this.find('.head-message');
                                $message.addClass('success').html(result.message);
                                $this.find('*').remove();
                                $this.append($message);
                            } else {
                                $this.find('.head-message').addClass('error').html(result.message);
                                if ('errors' in result) {
                                    for (let name in result.errors) {
                                        if (!result.errors.hasOwnProperty(name)) continue;
                                        let $log = $('[name="' + name + '"]').parent().find('.error-log');
                                        if (!$log.length) {
                                            $log = $('<div>').addClass('error-log');
                                            $(`[name="${name}"]`).after($log);
                                        }
                                        $log.addClass('error').html(result.errors[name]);
                                    }
                                }
                            }
                        }
                    },
                    error: function (result) {
                        if (window.debugMode) console.log("WRK_ERROR: " + result.responseText);
                    },
                    complete: function () {
                        $button.removeAttr('disabled');
                    },
                };
            $button.attr('disabled', 'disabled');
            $this.find('.error-log, .head-message').removeClass('error success').html('');
            let url = $this.attr("action");
            if (data.customAction) url = data.customAction;
            else if ($this.attr("action")) url = urlPrefix + url + urlPostfix;
            if ($this.attr('enctype')) {
                options.cache = false;
                options.processData = false;
                options.contentType = false;
            }
            $.ajax(url, options);
            return false;
        });
    }
}

//counters
export function initCountersPlugin(dispatchEvent = false) {
    $(document).on('click', '.wrk.counter .inc, .wrk.counter .dec, .wrk-counter .inc, .wrk-counter.dec', function () {
        const counter = $(this).closest('.counter, .wrk-counter').find('.count').get(0);
        if (!counter) return;
        let prop = counter.tagName === 'INPUT' ? 'value' : 'innerHtml';
        const factor = this.classList.contains('inc') ? 1 : -1;
        if ((counter.min || counter.dataset.min) === counter[prop] && factor < 0) return;
        counter[prop] = +counter[prop] + factor;
        if (dispatchEvent) {
            const event = new Event("input", {bubbles: true,});
            const tracker = counter._valueTracker;
            if (tracker) {
                tracker.setValue(counter.value);
            }
            counter.dispatchEvent(event);
        }
    });

    $(document).on('change', '.wrk.counter input, .wrk-counter input', function () {
        const min = this.min || this.dataset.min;
        if (!!min && this.value < min) this.value = min;
    });
}

//dropdowns
export function initDropDownsPlugin() {
    if ($('.wrk.dropdown, .wrk-dropdown').length) {
        $(document).on('click', function (e) {
            const $this = e.target.classList.contains('dropdown') ? $(e.target) : $(e.target).closest('.dropdown');
            if (!$this.length) {
                $('.dropdown.open').removeClass('open');
                return;
            }
            const $options = $this.find('.options');
            if (e.target === $this.get(0)) $this.toggleClass('open');
            $('.dropdown.open').not($this).removeClass('open');
            if ($this.hasClass('open')) {
                if ($(window).width() > 575) {
                    if ($this.get(0).getBoundingClientRect().left > $(window).width() / 2) {
                        $options.css({
                            right: 0,
                        });
                        $options.find('.pick').css({
                            right: 0,
                        });
                    } else {
                        $options.css({
                            left: 0,
                        });
                        $options.find('.pick').css({
                            left: 0,
                        });
                    }
                } else {
                    $options.css({
                        left: 'calc((100% - 250px) / 2)',
                    });
                    $options.find('.pick').css({
                        right: 0,
                        left: 0,
                        marginLeft: 'auto',
                        marginRight: 'auto',
                    });
                }
            } else {
                $options.removeAttr('style');
                $options.find('.pick').removeAttr('style');
            }
        });

        $(document).on('click', '.dropdown .reset', function () {
            $(this).closest('.dropdown').removeClass('applied').find(':checked').prop('checked', false);
            $(this).closest('.dropdown').find('[data-default]').each(function () {
                switch (this.tagName) {
                    case 'INPUT':
                        $(this).prop('checked', true);
                        break;
                    case 'OPTION':
                    case 'SELECT':
                        $(this).prop('selected', true);
                        break;
                    default:
                        $(this).addClass('.active');
                }
            });
            $(this).closest('form').trigger('submit');
        });

        $(document).on('click', '.dropdown .apply', function () {
            const $dropdown = $(this).closest('.dropdown');
            const applied = [];
            $dropdown.find(':checked, :selected, .active').each(function () {
                !!$(this).attr('data-caption') && applied.push($(this).attr('data-caption'));
            });
            const val = (applied.length > 1 ? applied.length : applied[0]);
            $dropdown.data('applied-text') ? $dropdown.attr('data-applied-caption', $dropdown.data('applied-text') + val) : $dropdown.attr('data-applied-caption', val);
            $dropdown.removeClass('open').addClass('applied');
        });
    }
}

//mask
export function initMaskPlugin() {
    $('.wrk.masked-phone, .wrk-masked-phone').mask("8-000-000-0000");
    $('.wrk.masked-date, .wrk-masked-date').mask("00.00.0000");
    $('.wrk.masked-sdate, .wrk-masked-sdate').mask("00.00.00");
    $('.wrk.masked-datetime, .wrk-masked-datetime').mask("00.00.0000 00:00:00");
    $('.wrk.masked-phone-simple, .wrk-masked-phone-simple').mask("00-00-00");
    $('.wrk.masked-phone-mobile, .wrk-masked-phone-mobile').mask("8(000)000-00-00");
    $.applyDataMask();
}

export function altPhoneMask(selector = '.wrk.masked-phone-alt') {
    const $selector = typeof selector === 'string' ? $(selector) : selector;
    const options = {
        onKeyPress: function (cep, e, field, options) {
            const masks = ['+7 (000) 000-00-00', '00-00-00',];
            const mask = (cep.length > 6) ? masks[1] : masks[0];
            options.placeholder = (cep.length > 6) ? "+7 (___) ___-__-__" : "__-__-__";
            $selector.mask(mask, options);
        },
    };

    $selector.mask('+7 (000) 000-00-00', options);
}

//modal
export function initModalPlugin() {
    const defaultSettings = {
            closeBtn: false,
            padding: 0,
            margin: 0,
            wrapCSS: "modal-customized",
        },
        modalSets = {};

    $('.wrk.modal, .wrk-modal').not('[rel]').each(function () {
        const $this = $(this),
            customSets = $this.data("settings"),
            settings = $.extend({}, defaultSettings);
        if (customSets === "default") $this.fancybox();
        else if (typeof customSets !== 'undefined') {
            if (customSets in modalSets) {
                $this.fancybox(modalSets[customSets]);
            } else {
                $.ajax({
                    url: customSets + ".json",
                    dataType: 'json',
                    async: false,
                    dataFilter: function (result) {
                        result = typeof result === "string" ? JSON.parse(result) : result;
                        for (let sets in result) {
                            if (!result.hasOwnProperty(sets)) continue;
                            if (sets.indexOf("eval:") > -1) {
                                sets = sets.replace(/eval:/, "");
                                if (window.hasOwnProperty(result[sets])) settings[sets] = window[result[sets]];
                            } else {
                                settings[sets] = result[sets];
                            }
                        }
                        modalSets[customSets] = $.extend({}, settings);
                    },
                    success: function () {
                        $this.fancybox(modalSets[customSets]);
                    },
                    error: function (result) {
                        $this.fancybox(settings);
                        if (window.debugMode) console.log("WRK_ERROR: " + result.responseText);
                    },
                });
            }
        } else $this.fancybox(settings);
    });

    if ($('.wrk.modal[rel], .wrk-modal[rel]').length) {
        $('.wrk.modal[rel], .wrk-modal[rel]').fancybox(defaultSettings);
    }

    $(document).on('click', '.wrk.modal-dismiss, .wrk-modal-dismiss', function () {
        $.fancybox.close();
    });
}

export function modalMsg(data = {showTime: 0,}) {
    if ($('#modal-msg.wrk').length > 0) {
        let caption = (data.caption) ? data.caption : (data.status === "success") ? "Успешно!" : "Ошибка!";
        $(".modal-caption.wrk, .modal-message.wrk").html("");
        $(".modal-caption.wrk").html(caption);
        $(".modal-message.wrk").html(data.message);
        $.fancybox({"href": "#modal-msg", closeBtn: false, minWidth: 300,});
        if (data.showTime > 0) {
            setTimeout(function () {
                if (data.redirect) location.href = data.redirect;
                else if (data.reload) location.reload();
                else $.fancybox.close();
            }, data.showTime);
        }
    }
}

export function setModal(selector = '.modal-trigger', settings = false) {
    const defSettings = {
        baseClass: 'wrk-modal',
        src: $(selector).attr('href'),
        smallBtn: false,
        buttons: false,
        arrows: false,
        defaultType: 'html',
        image: {
            preload: true,
        },
    };
    if (!settings) settings = defSettings;
    else settings = Object.assign(defSettings, settings);
    $(document).on('click', selector, function (e) {
        e.preventDefault();
        $.fancybox.open(settings);
    });
}

//parallax
export function initParallaxPlugin() {

    function parallax(pos) {
        $('.mid-layer, .wrk-mid-layer').css({
            transform: "translate(0, -" + pos * 0.5 + "px)",
        });
        $('.back-layer, .wrk-back-layer').css({
            transform: "translate(0, -" + pos * 0.2 + "px)",
        });

    }

    if ($('.wrk.parallax, .wrk-parallax').length > 0) {

        $('.wrk.parallax, .wrk-parallax').on('resize', function () {

            $('.mid-layer').css({
                height: document.body.scrollHeight * 1.5 + 'px',
            });

            $('.back-layer').css({
                height: document.body.scrollHeight * 1.2 + 'px',
            });
        }).trigger('resize');
        $(document).on('scroll', function () {
            parallax($(this).scrollTop());
        });
    }
}

//scroll
export function initScrollPlugin() {
    $(document).on('click', '.wrk.scroller, .wrk-scroller', function (e) {
        e.preventDefault();
        let scrollEl = $(this).attr('href');
        if ($(scrollEl).length) {
            $('html, body').animate({
                scrollTop: $(scrollEl).offset().top,
            }, 600);
        }
        return false;
    });
}

//slider
export function initSliderPlugin(settings = {}) {
    const $selector = $(settings.selector || '.wrk.slider, .wrk-slider');
    const defaultSettings = {
        //default values:
        // accessibility: true,    //true
        //adaptiveHeight: false,  //false
        autoplay: true,    //false
        autoplaySpeed: 5000,    //3000
        arrows: true,   //true
        //asNavFor: null, //null
        // appendArrows: $(element).find('.slider-controls'),   //element
        // appendDots: $(element).find('.slider-dots'),   //element
        //prevArrow: $(element),  //element
        //nextArrow: $(element),  //element
        //centerMode: false, //false
        //centerPadding: '50px',  //50px
        //cssEase: 'ease',    //ease
        //customPaging: null,  //n/a
        dots: true,    //false
        // draggable: true,    //true
        //fade: false,    //false
        //focusOnSelect: false,   //false
        //easing: 'linear',   //linear
        //edgeFriction: 0.15, //0.15
        // infinite: true, //true
        //initialSlide: 0,    //0
        //lazyLoad: 'ondemand',   //ondemand (progressive)
        //mobileFirst: false, //false
        //pauseOnHover: true, //true
        //pauseOnDotsHover: false,    //false
        //respondTo: 'window',    //window
        //responsive: null,   //none
        //rows: 1,    //1
        //slide: '', //''
        //slidesPerRow: 1,    //1
        //slidesToShow: 1,    //1
        //slidesToScroll: 1,  //1
        //speed: 300, //300
        //swipe: true,    //true
        //swipeToSlide: false, //false
        //touchMove: true,    //true
        //touchThreshold: 5, //5
        //useCSS: true, //true
        //variableWidth: false, //false
        //vertical: false, //false
        //verticalSwiping: false, //false
        //rtl: false //false,
    };
    $selector.each(function () {
        const $slider = $(this).find('.slider-container');
        const classPrefix = $(this).data('compatible') ? 'wrk-' : '';
        if (typeof settings === 'object') {
            settings = $.extend({}, defaultSettings);
        } else {
            settings = defaultSettings;
        }
        if ($(this).data('settings')) {
            let sets = $(this).data('settings');
            if (typeof sets === 'string') {
                sets = JSON.parse(sets);
            }
            for (let s in sets) {
                if (!sets.hasOwnProperty(s)) {
                    continue;
                }
                settings[s] = sets[s];
            }
        }

        if (!$(this).hasClass('slave')) {
            if (settings.arrows === true) {
                $(this).append($('<div>').addClass(classPrefix + 'slider-controls').append([
                    $('<div>').addClass(classPrefix + 'prev'),
                    $('<div>').addClass(classPrefix + 'next'),
                ]));
                settings.prevArrow = $(this).find('.' + classPrefix + 'prev');
                settings.nextArrow = $(this).find('.' + classPrefix + 'next');
            }
            if (settings.dots === true) {
                if (!settings.appendDots) {
                    $(this).append($('<div>').addClass(classPrefix + 'slider-dots'));
                    settings.appendDots = $(this).find('.' + classPrefix + 'slider-dots');
                    settings.dotsClass = classPrefix + 'slider-dots-list';
                } else {
                    const $appendable = $(this).find(settings.appendDots);
                    if ($appendable.length) settings.appendDots = $appendable;
                    settings.dotsClass = classPrefix + 'slider-dots';
                }
            }
        }

        try {
            if (!!$(this).data("events")) {
                let events = $(this).data("events");
                if (typeof events === 'string') {
                    events = JSON.parse(events);
                }
                for (let event in events) {
                    if (!events.hasOwnProperty(event)) {
                        continue;
                    }
                    switch (event) {
                        case "init":
                            events[event]($slider);
                            break;
                        default:
                            $slider.on(event, events[event].bind(this));
                    }
                }
            }
        } catch (e) {
            console.error('WRK Slider: cannot retrieve events: ' + e);
        }

        if ($(this).hasClass('master')) {
            settings.asNavFor = (settings.asNavFor || ('.' + classPrefix + 'slider.slave')) + ' .slider-container';
            $slider.slick(settings);
        } else {
            if ($(this).hasClass('slave')) {
                settings.arrows = false;
                settings.dots = false;
            }
            $slider.slick(settings);
        }
    });
}

export function setSlider($slider = $('.wrk.slider, .wrk-slider'), prefs = {}, init = () => {
}) {
    prefs = Object.assign({
        autoplay: false,
        adaptiveHeight: true,
        prevArrow: $slider.find('.prev'),
        nextArrow: $slider.find('.next'),
        easing: 'ease-out',
        dots: true,
        appendDots: $slider.find('.slider-dots'),
    }, prefs || {});
    $slider.find('.slider-container').on({
        init: typeof init === 'function' ? init : null,
        reInit: typeof init === 'function' ? init : null,
    }).slick(prefs);
    return $slider.find('.slider-container');
}

//sticky
export function initStickyPlugin() {
    $(".wrk.sticky, .wrk-sticky").sticky({topSpacing: 0,});
}

export function setSticky($selector = '.sticky', settings = {topSpacing: 0,}) {
    if (typeof $selector === 'string') $selector = $($selector);
    $selector.sticky(settings);
}

//text-scroller
export function setToggleScroller($selector = '.wrk.text-scroll, .wrk-text-scroller') {
    $(document).on('click', $selector + ' .dismiss', function () {
        $(this).closest($selector).fadeOut(500);
    });
    $selector = $($selector);
    $selector.fadeIn(500);
    const REM = parseInt(getComputedStyle(document.body).fontSize || 16) * 0.5;
    const $content = $selector.find('.content');
    const scHeight = $content[0].scrollHeight;
    const time = Math.ceil((scHeight - $content[0].clientHeight) / REM) * 1000;
    const scrollText = () => {
        $content.animate({
            scrollTop: scHeight,
        }, time, () => {
            $content.animate({
                scrollTop: 0,
            }, 1000, scrollText);
        });
    };
    scrollText();
}

//init
export function initPlugins(selector = 'meta[name="plugins"]') {
    let plugins = null;
    if ($(selector).length) {
        plugins = $(selector).attr("content").split(";");
        if (!plugins[plugins.length - 1]) plugins.pop();
    }

    if (!!plugins) {
        for (let i = 0; i < plugins.length; i++) {
            switch (plugins[i]) {
                case "accordeon":
                    initAccordeonPlugin();
                    break;
                case "ajax-forms":
                    initAjaxFormsPlugin();
                    break;
                case "counters":
                    initCountersPlugin();
                    break;
                case "dropdowns":
                    initDropDownsPlugin();
                    break;
                case "mask":
                    initMaskPlugin();
                    break;
                case "modal":
                    initModalPlugin();
                    break;
                case "parallax":
                    initParallaxPlugin();
                    break;
                case "scroll":
                    initScrollPlugin();
                    break;
                case "slider":
                    initSliderPlugin();
                    break;
                case "sticky":
                    initStickyPlugin();
                    break;
            }
        }
    }
}

//Under development...
export function starsBG($selector) {
    if (!$selector || !$selector.length) return;
    const
        count = $selector.data('count') || 10,
        $container = $('<div>').addClass('stars'),
        pos = {
            x: getRandomInt(0, $selector.width()),
            y: getRandomInt(0, $selector.height()),
        },
        stars = [],
        hW = $selector.width() / 2,
        hH = ($selector.height() / 100 * ($selector.data('gradient') || 100)) / 2;
    let delay = 0, f = 1;
    for (let i = 0; i < count; i++) {
        const $star = $('<span>').css({
            left: pos.x + 'px',
            top: pos.y + 'px',
            animationDelay: delay + 'ms',
        });
        const x = [0, hW * 2,];//pos.x >= hW ? [0, hW] : [hW, hW*2];
        const y = [0, hH * 2,];//pos.y >= hH ? [0, hH] : [hW, hH*2];

        stars.push($star);
        pos.x = getRandomInt(...x);
        pos.y = getRandomInt(...y);
        delay += 29 + (f += 1.5);
    }
    $selector.append($container.append($('<div>').addClass('canvas').append(stars)));
}

export function initOptionConfigurator() {

    $(document).on('click', '.wrk .oc-option', function (e) {
        e.preventDefault();
        if ($(this).hasClass('active')) {
            return false;
        }
        const $this = $(this),
            $conf = $this.closest('.wrk.opt-conf, .wrk-opt-conf'),
            $cont = $conf.find('.oc-cont, .wrk-oc-cont'),
            $opts = $conf.find('.oc-options, .wrk-oc-options'),
            option = `[data-option="${$this.data('option')}"]`,
            module = $this.data('module') ? `[data-module="${$this.data('module')}"]` : '';
        $opts.find(module + '.active').removeClass('active');
        $cont.find(module + '.active').removeClass('active');
        $cont.find(module + option).addClass('active');
        $this.addClass('active');
    });

    $(document).on('click', '.wrk .oc-sect-nav', function (e) {
        e.preventDefault();
        if ($(this).hasClass('active')) {
            return false;
        }
        const $this = $(this),
            $conf = $this.closest('.wrk.opt-conf, .wrk-opt-conf'),
            section = `[data-section="${$this.data('section')}"]`;
        $this.parent('.oc-sect-navs').find('.active').removeClass('active');
        $conf.find('[data-section].active').removeClass('active');
        $conf.find(section).addClass('active');
    });
}

export function initTabs(selector = '.wrk .tab:not(.unwrk), .wrk-tab') {
    $(document).on('click', selector, function (e) {
        e.preventDefault();
        const $this = $(this),
            $cont = $this.closest('.tabs, .wrk-tabs'),
            $content = $cont.find('.tabs-cont, .wrk-tabs-cont');
        $cont.find('[data-tab].active').removeClass('active');
        $this.addClass('active');
        $content.find(`[data-tab="${$this.data('tab')}"]`).addClass('active');
    });
}

export function initVideoPlayer() {
    $(document).on('click', '.wrk.video-player .option, .wrk-videoplayer .option', function (e) {
        if (e.currentTarget.tagName === 'A') e.preventDefault();
        const src = $(this).attr('href') || this.dataset.src;
        const $player = $(this).closest('.video-player, .wrk-video-player');
        if (!src || src[0] === '#' || $player.attr('src') === src) return;
        const poster = this.dataset.poster;
        $player.find('.option.active').removeClass('active');
        $(this).addClass('active');
        $player.find('video').attr({
            src: src,
            poster: poster || '',
        });
    });
}