import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import LoadingButton from './components/LoadingButton.vue';
import AsyncSelect from './components/AsyncSelect.vue';
import AppIcon from './components/AppIcon.vue';
import IconActionButton from './components/IconActionButton.vue';
import { clickLock, submitLock, installGlobalButtonLock } from './directives/clickLock';
import tableCards from './directives/tableCards.js';
import { isLoading } from './services/api';
import { initTheme } from './utils/theme';

initTheme();

const app = createApp(App);
app.component('LoadingButton', LoadingButton);
app.component('AsyncSelect', AsyncSelect);
app.component('AppIcon', AppIcon);
app.component('IconActionButton', IconActionButton);
app.directive('click-lock', clickLock);
app.directive('submit-lock', submitLock);
app.directive('table-cards', tableCards);
app.use(router);
installGlobalButtonLock(isLoading);
app.mount('#app');
