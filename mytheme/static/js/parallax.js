'use strict';

((root) => {
    const parallax = (element) => {
        var last_ScrollY = -1;
        var scheduled_scroll = false;
    
        const transformType = 'webkitTransform' in document.body.style ? 'webkitTransform' : 'transform';
        const item = document.getElementById(element);
        if (!item) {
            return false;
        }
        if (typeof requestAnimationFrame !== 'function') {
            return false;
        }

        window.addEventListener('scroll', () => onScroll());

        function onScroll() {
            last_ScrollY = window.scrollY;
            if (scheduled_scroll) {
                // should not happen
                return;
            }
            scheduled_scroll = true;
            requestAnimationFrame(() => {
                let translate = last_ScrollY;
                item.style['background-position-y'] = `calc(50% + ${(translate * -0.5)}px)`;
                scheduled_scroll = false;
             });
        }
    }

    if (typeof module === 'object') {
        module.exports = parallax;
    } else {
        root.parallax = parallax;
    }
})(this);
