/*
 * "Instalar app" in the account menu (arquitectura-frontend.md, 6).
 *
 * Chrome (Android, desktop) fires `beforeinstallprompt`: it's kept and shown
 * when the employee taps the menu item. iOS has no way to trigger the
 * install from a page, so there the item opens the steps drawer instead.
 */
let installPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
});

window.aridanInstall = {
    /** Already opened as an installed app (home screen icon). */
    isInstalled() {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    },

    /** iPhone / iPad (iPadOS reports itself as a Mac with touch). */
    isIos() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    },

    /** Shows the browser's own install dialog. False when there is none to show. */
    async prompt() {
        if (!installPrompt) {
            return false;
        }

        const event = installPrompt;
        installPrompt = null;
        await event.prompt();

        return true;
    },
};
