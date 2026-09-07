import { createApp } from 'vue';

import AutoHide from './components/AutoHide.vue';
import ConfirmButton from './components/ConfirmButton.vue';
import StarRating from './components/StarRating.vue';

/**
 * Every Vue component that a Blade view is allowed to mount.
 * Add new ones here, then use them from Blade with:
 *
 *   <div data-vue="StarRating" data-props='@json(['name' => 'rating'])'></div>
 */
const islands = {
    AutoHide,
    ConfirmButton,
    StarRating,
};

document.querySelectorAll('[data-vue]').forEach((el) => {
    const component = islands[el.dataset.vue];

    if (! component) {
        console.warn(`Unknown Vue island: "${el.dataset.vue}"`);

        return;
    }

    createApp(component, JSON.parse(el.dataset.props || '{}')).mount(el);
});

const dismissibleMenus = () => document.querySelectorAll('details[data-dismiss-on-outside-click][open]');

document.addEventListener('click', (event) => {
    dismissibleMenus().forEach((details) => {
        if (! details.contains(event.target)) {
            details.open = false;
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    dismissibleMenus().forEach((details) => {
        details.open = false;
        details.querySelector('summary')?.focus();
    });
});
