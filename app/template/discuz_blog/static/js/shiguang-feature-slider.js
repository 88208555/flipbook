(function () {
    'use strict';

    function initSlider(root) {
        if (!root || root.getAttribute('data-sg-ready') === '1') {
            return;
        }

        var items = root.querySelectorAll('[data-sg-feature-order]');
        var dotsRoot = root.querySelector('.sg-diy-feature-dots');
        var slides = [];
        var dots = [];
        var active = 0;
        var timer = null;
        var i;

        for (i = 0; i < items.length; i++) {
            if (parseInt(items[i].getAttribute('data-sg-feature-order'), 10) <= 5) {
                slides.push(items[i]);
            }
        }

        if (!slides.length || !dotsRoot) {
            return;
        }

        root.setAttribute('data-sg-ready', '1');
        root.className += ' has-js';

        for (i = 0; i < slides.length; i++) {
            var dot = document.createElement('button');
            dot.type = 'button';
            dot.setAttribute('data-sg-slide-to', i);
            dot.setAttribute('aria-label', '第 ' + (i + 1) + ' 张：' + (slides[i].querySelector('h2') ? slides[i].querySelector('h2').innerText : '精选内容'));
            dotsRoot.appendChild(dot);
            dots.push(dot);
        }

        function show(index) {
            active = (index + slides.length) % slides.length;

            for (i = 0; i < slides.length; i++) {
                slides[i].className = slides[i].className.replace(/\s*is-active/g, '');
                slides[i].setAttribute('aria-hidden', i === active ? 'false' : 'true');
                if (i === active) {
                    slides[i].className += ' is-active';
                }
            }

            for (i = 0; i < dots.length; i++) {
                dots[i].className = dots[i].className.replace(/\s*is-active/g, '');
                dots[i].setAttribute('aria-current', i === active ? 'true' : 'false');
                if (i === active) {
                    dots[i].className += ' is-active';
                }
            }
        }

        function stop() {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function start() {
            stop();
            if (slides.length > 1) {
                timer = window.setInterval(function () {
                    show(active + 1);
                }, 5000);
            }
        }

        for (i = 0; i < dots.length; i++) {
            (function (index) {
                dots[index].onclick = function () {
                    show(index);
                    start();
                };
            })(i);
        }

        root.onmouseenter = stop;
        root.onmouseleave = start;
        root.onfocusin = stop;
        root.onfocusout = start;

        show(0);
        start();
    }

    function boot() {
        var sliders = document.querySelectorAll('[data-sg-feature-slider]');
        for (var i = 0; i < sliders.length; i++) {
            initSlider(sliders[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
