import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import PosPage from './pos/PosPage.vue';

const posRoot = document.getElementById('pos-app');

if (posRoot) {
    const app = createApp(PosPage, {
        bootstrap: JSON.parse(posRoot.dataset.bootstrap ?? '{}'),
    });

    app.use(createPinia());
    app.mount(posRoot);
}
