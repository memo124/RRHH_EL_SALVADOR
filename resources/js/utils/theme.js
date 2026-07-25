import { ref } from 'vue';
import api from '../services/api';

export const themePreference = ref('auto');
export const resolvedTheme = ref('light');

const THEME_KEY = 'themePreference';
let autoTimer = null;
let systemMediaQuery = null;

const VALID_PREFERENCES = ['light', 'dark', 'auto', 'system'];

export function getCurrentUserId() {
  try {
    const user = JSON.parse(localStorage.getItem('user') || 'null');
    return user?.id ?? null;
  } catch {
    return null;
  }
}

export function getUserThemeFromStorage() {
  try {
    const user = JSON.parse(localStorage.getItem('user') || 'null');
    if (VALID_PREFERENCES.includes(user?.theme)) {
      return user.theme;
    }
  } catch {
    /* ignore */
  }
  return null;
}

/** Tema según preferencia del sistema operativo / navegador. */
export function getSystemTheme() {
  if (typeof window === 'undefined') {
    return 'light';
  }
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

/** Oscuro de 18:00 a 05:59, claro de 06:00 a 17:59. */
export function getHourBasedTheme(date = new Date()) {
  const hour = date.getHours();
  return hour >= 18 || hour < 6 ? 'dark' : 'light';
}

export function resolveTheme(preference) {
  if (preference === 'system') {
    return getSystemTheme();
  }
  if (preference === 'auto') {
    return getHourBasedTheme();
  }
  return preference === 'dark' ? 'dark' : 'light';
}

export function applyTheme(preference) {
  const resolved = resolveTheme(preference);
  document.documentElement.classList.toggle('dark', resolved === 'dark');
  resolvedTheme.value = resolved;
  return resolved;
}

function readSavedPreference() {
  const fromUser = getUserThemeFromStorage();
  if (fromUser) {
    return fromUser;
  }

  const saved = localStorage.getItem(THEME_KEY);
  if (VALID_PREFERENCES.includes(saved)) {
    return saved;
  }

  return getCurrentUserId() ? 'auto' : 'system';
}

function stopAutoThemeWatcher() {
  if (autoTimer) {
    clearInterval(autoTimer);
    autoTimer = null;
  }
}

function stopSystemThemeWatcher() {
  if (systemMediaQuery) {
    systemMediaQuery.removeEventListener('change', onSystemThemeChange);
    systemMediaQuery = null;
  }
}

function onSystemThemeChange() {
  if (themePreference.value === 'system') {
    applyTheme('system');
  }
}

function startAutoThemeWatcher() {
  stopAutoThemeWatcher();
  autoTimer = setInterval(() => {
    if (themePreference.value === 'auto') {
      applyTheme('auto');
    }
  }, 60_000);
}

function startSystemThemeWatcher() {
  stopSystemThemeWatcher();
  if (typeof window === 'undefined') {
    return;
  }
  systemMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
  systemMediaQuery.addEventListener('change', onSystemThemeChange);
}

function syncWatchers(preference) {
  stopAutoThemeWatcher();
  stopSystemThemeWatcher();

  if (preference === 'auto') {
    startAutoThemeWatcher();
  } else if (preference === 'system') {
    startSystemThemeWatcher();
  }
}

export function initTheme() {
  themePreference.value = readSavedPreference();
  applyTheme(themePreference.value);
  syncWatchers(themePreference.value);

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && ['auto', 'system'].includes(themePreference.value)) {
      applyTheme(themePreference.value);
    }
  });
}

export function loadUserTheme(theme = getUserThemeFromStorage()) {
  const pref = VALID_PREFERENCES.includes(theme) ? theme : 'auto';
  setThemePreference(pref, { saveRemote: false });
}

function updateStoredUserTheme(preference) {
  try {
    const user = JSON.parse(localStorage.getItem('user') || 'null');
    if (!user) {
      return;
    }
    user.theme = preference;
    localStorage.setItem('user', JSON.stringify(user));
  } catch {
    /* ignore */
  }
}

export async function setThemePreference(preference, { saveRemote = true } = {}) {
  if (!VALID_PREFERENCES.includes(preference)) {
    return;
  }

  themePreference.value = preference;
  localStorage.setItem(THEME_KEY, preference);
  localStorage.removeItem('theme');
  applyTheme(preference);
  syncWatchers(preference);
  updateStoredUserTheme(preference);

  if (saveRemote && getCurrentUserId()) {
    try {
      await api.put('/user/theme', { theme: preference });
    } catch {
      /* ignore */
    }
  }
}

export function themePreferenceDescription(preference = themePreference.value) {
  if (preference === 'light') return 'Siempre tema claro.';
  if (preference === 'dark') return 'Siempre tema oscuro.';
  if (preference === 'system') {
    const current = getSystemTheme() === 'dark' ? 'oscuro' : 'claro';
    return `Usa la preferencia de su navegador (ahora ${current}).`;
  }
  const current = getHourBasedTheme() === 'dark' ? 'oscuro' : 'claro';
  return `Oscuro 18:00–06:00, claro el resto del día (ahora ${current}).`;
}
