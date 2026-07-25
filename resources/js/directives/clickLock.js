import { watch } from 'vue';

function setBusy(el, busy) {
  if (busy) {
    el.dataset.clickLock = '1';
    el.setAttribute('aria-busy', 'true');
    el.classList.add('is-click-loading');
    el._clickLockWasDisabled = el.disabled;
    el.disabled = true;

    if (!el.querySelector('.btn-spinner')) {
      const spinner = document.createElement('span');
      spinner.className = 'btn-spinner';
      spinner.setAttribute('aria-hidden', 'true');
      el.prepend(spinner);
    }
  } else {
    delete el.dataset.clickLock;
    el.removeAttribute('aria-busy');
    el.classList.remove('is-click-loading');
    el.disabled = el._clickLockWasDisabled ?? false;
    el.querySelector('.btn-spinner')?.remove();
  }
}

function setBusyVisual(el, busy) {
  if (busy) {
    el.setAttribute('aria-busy', 'true');
    el.classList.add('is-click-loading');

    if (!el.querySelector('.btn-spinner')) {
      const spinner = document.createElement('span');
      spinner.className = 'btn-spinner';
      spinner.setAttribute('aria-hidden', 'true');
      el.prepend(spinner);
    }
  } else {
    el.removeAttribute('aria-busy');
    el.classList.remove('is-click-loading');
    el.querySelector('.btn-spinner')?.remove();
  }
}

function shouldSkipGlobalLock(btn) {
  if (btn.hasAttribute('data-no-lock')) {
    return true;
  }
  if (btn.hasAttribute('data-click-lock-managed')) {
    return true;
  }
  if (btn.type === 'submit' && btn.form?.hasAttribute('data-submit-lock')) {
    return true;
  }
  return false;
}

async function runLocked(el, fn, event) {
  if (el.dataset.clickLock === '1') {
    event?.preventDefault?.();
    event?.stopImmediatePropagation?.();
    return;
  }

  setBusy(el, true);
  try {
    return await fn(event);
  } finally {
    setBusy(el, false);
  }
}

/** Bloqueo global: spinner en el botón pulsado mientras hay petición API activa. */
export function installGlobalButtonLock(isLoadingRef) {
  const pendingButtons = new WeakSet();
  let activeButton = null;

  const releaseButton = (btn) => {
    if (!btn) {
      return;
    }
    pendingButtons.delete(btn);
    setBusyVisual(btn, false);
    if (activeButton === btn) {
      activeButton = null;
    }
  };

  document.addEventListener(
    'click',
    (event) => {
      const btn = event.target.closest('button');
      if (!btn || shouldSkipGlobalLock(btn)) {
        return;
      }

      if (pendingButtons.has(btn)) {
        event.preventDefault();
        event.stopImmediatePropagation();
        return;
      }

      pendingButtons.add(btn);
      activeButton = btn;

      setTimeout(() => {
        if (activeButton === btn && !isLoadingRef.value) {
          releaseButton(btn);
        }
      }, 250);
    },
    true
  );

  watch(isLoadingRef, (loading) => {
    if (!activeButton) {
      return;
    }

    if (loading) {
      setBusyVisual(activeButton, true);
    } else {
      releaseButton(activeButton);
    }
  });
}

export { setBusy, runLocked };

export const clickLock = {
  mounted(el, binding) {
    const fn = binding.value;
    if (typeof fn !== 'function') {
      return;
    }

    el.setAttribute('data-click-lock-managed', '');
    el._clickLockHandler = (event) => runLocked(el, fn, event);
    el.addEventListener('click', el._clickLockHandler);
  },
  updated(el, binding) {
    if (typeof binding.value !== 'function') {
      return;
    }
    el._clickLockFn = binding.value;
  },
  unmounted(el) {
    el.removeAttribute('data-click-lock-managed');
    if (el._clickLockHandler) {
      el.removeEventListener('click', el._clickLockHandler);
    }
  },
};

/** Bloquea envíos de formulario mientras la acción async está en curso. */
export const submitLock = {
  mounted(el, binding) {
    const fn = binding.value;
    if (typeof fn !== 'function') {
      return;
    }

    el.setAttribute('data-submit-lock', '');
    el._submitLockHandler = (event) => {
      event.preventDefault();
      const submitter = event.submitter || el.querySelector('[type="submit"]');
      const lockTarget = submitter || el;
      runLocked(lockTarget, fn, event);
    };
    el.addEventListener('submit', el._submitLockHandler);
  },
  unmounted(el) {
    el.removeAttribute('data-submit-lock');
    if (el._submitLockHandler) {
      el.removeEventListener('submit', el._submitLockHandler);
    }
  },
};
