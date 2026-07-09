import { createInertiaApp, router } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import { toast } from 'sonner';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import ClientLayout from '@/layouts/client-layout';
import SettingsLayout from '@/layouts/settings/layout';

configureEcho({
    broadcaster: 'reverb',
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// CSRF recovery: when a page has been open long enough for the session token to
// expire (or was restored from the back/forward cache), the first POST comes back
// as a 419 "Page Expired" — which surfaces to users as a failed first attempt that
// works on retry. Intercept it, silently refresh the XSRF-TOKEN cookie, and ask the
// user to submit again — their form input is preserved.
let refreshingCsrf = false;
router.on('httpException', (event) => {
    const status = event.detail.response?.status;

    if (status !== 419) {
        return;
    }

    event.preventDefault();

    if (refreshingCsrf) {
        return;
    }

    refreshingCsrf = true;

    // A plain GET to the current URL makes Laravel issue a fresh XSRF-TOKEN cookie,
    // which Inertia reads on the next request — no navigation, no lost input.
    void fetch(window.location.href, { credentials: 'same-origin' })
        .catch(() => undefined)
        .finally(() => {
            refreshingCsrf = false;
            toast.error('Your session expired', {
                description: 'Please submit the form again.',
            });
        });
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return null;
            case name.startsWith('consultation/'):
                return null;
            case name.startsWith('portal/'):
                return ClientLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
            case name === 'admin/settings':
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#F5A623',
    },
});

initializeTheme();
