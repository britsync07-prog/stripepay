import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Panel } from '@/components/panel';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/portal/profile';

export default function Profile({
    profile,
}: {
    profile: { name: string; email: string };
}) {
    const { data, setData, patch, processing, errors } = useForm({
        name: profile.name ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(update().url, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Profile" />
            <div className="mx-auto max-w-xl animate-fade-up space-y-6">
                <PageHeader
                    eyebrow="Portal"
                    title="Profile"
                    description="Manage your account details."
                />

                <Panel>
                    <form onSubmit={submit} className="space-y-5">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Full name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                autoComplete="name"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={profile.email}
                                readOnly
                                aria-readonly
                                className="cursor-not-allowed opacity-70"
                            />
                            <p className="text-xs text-muted-foreground">
                                Your email address can't be changed.
                            </p>
                        </div>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner className="mr-2" />}
                                Save changes
                            </Button>
                        </div>
                    </form>
                </Panel>
            </div>
        </>
    );
}
