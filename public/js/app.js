(() => {
  const addToggle = document.querySelector('[data-mobile-add]');
  const sheet = document.querySelector('[data-mobile-sheet]');
  const overlay = document.querySelector('[data-mobile-overlay]');

  const closeSheet = () => {
    sheet?.classList.remove('active');
    overlay?.classList.remove('active');
    document.body.style.overflow = '';
  };

  addToggle?.addEventListener('click', () => {
    sheet?.classList.add('active');
    overlay?.classList.add('active');
    document.body.style.overflow = 'hidden';
  });

  overlay?.addEventListener('click', closeSheet);
  document.querySelectorAll('[data-close-sheet]').forEach((el) => el.addEventListener('click', closeSheet));

  document.querySelectorAll('[data-auto-submit]').forEach((el) => {
    el.addEventListener('change', () => el.form?.submit());
  });

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
  }

  let deferredPrompt = null;
  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    document.querySelectorAll('[data-install-app]').forEach((button) => button.classList.remove('hidden'));
  });

  document.querySelectorAll('[data-install-app]').forEach((button) => {
    button.addEventListener('click', async () => {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      await deferredPrompt.userChoice;
      deferredPrompt = null;
    });
  });
})();
