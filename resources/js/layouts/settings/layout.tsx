import { Link, usePage } from '@inertiajs/react';
import { Building2, UserRound } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { settings } from '@/routes/admin';
import { edit } from '@/routes/profile';
import type { NavItem } from '@/types';

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const isAdmin = usePage().props.auth?.isAdmin ?? false;

    const sidebarNavItems: NavItem[] = [
        {
            title: 'Profile',
            href: edit(),
            icon: UserRound,
        },
        ...(isAdmin
            ? [
                  {
                      title: 'Platform',
                      href: settings(),
                      icon: Building2,
                  },
              ]
            : []),
    ];

    return (
        <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
            <PageHeader
                eyebrow="Settings"
                title="Settings"
                description="Manage your profile and platform settings."
            />

            <div className="flex flex-col gap-6 lg:flex-row lg:gap-10">
                <aside className="shrink-0 lg:w-56">
                    <nav className="flex flex-col gap-1" aria-label="Settings">
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn(
                                    'w-full justify-start',
                                    isCurrentOrParentUrl(item.href) &&
                                        'bg-primary/12 font-medium text-primary ring-1 ring-primary/25 ring-inset hover:bg-primary/12 hover:text-primary',
                                )}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="size-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <div className="min-w-0 flex-1 lg:max-w-2xl">{children}</div>
            </div>
        </div>
    );
}
