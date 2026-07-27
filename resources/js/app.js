import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import LoadingButton from './components/LoadingButton.vue';
import AsyncSelect from './components/AsyncSelect.vue';
import { clickLock, submitLock, installGlobalButtonLock } from './directives/clickLock';
import { isLoading } from './services/api';
import { initTheme } from './utils/theme';

initTheme();

const app = createApp(App);
app.component('LoadingButton', LoadingButton);
app.component('AsyncSelect', AsyncSelect);
app.directive('click-lock', clickLock);
app.directive('submit-lock', submitLock);
app.use(router);
installGlobalButtonLock(isLoading);
app.mount('#app');
