import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { Save, UserPlus } from 'lucide-react';
import { Controller, useForm } from 'react-hook-form';
import Select from 'react-select';
import { z } from 'zod';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store, update } from '@/routes/admin/clients';
import type { SelectOption } from '@/types';

const schema = z.object({
    name: z.string().min(1, 'Name is required').max(255),
    email: z.string().email('Enter a valid email').max(255),
    company: z.string().max(255).optional().or(z.literal('')),
    country: z.string().length(2).optional().or(z.literal('')),
    phone: z.string().max(50).optional().or(z.literal('')),
});

type ClientForm = z.infer<typeof schema>;

type ClientInit = {
    id: number;
    name: string;
    email: string;
    company: string | null;
    country: string | null;
    phone: string | null;
};

export function ClientFormDialog({
    open,
    onOpenChange,
    countries,
    client,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    countries: SelectOption[];
    client?: ClientInit;
}) {
    const editing = Boolean(client);
    const {
        register,
        handleSubmit,
        control,
        reset,
        formState: { errors, isSubmitting },
    } = useForm<ClientForm>({
        resolver: zodResolver(schema),
        defaultValues: {
            name: client?.name ?? '',
            email: client?.email ?? '',
            company: client?.company ?? '',
            country: client?.country ?? '',
            phone: client?.phone ?? '',
        },
    });

    const submit = (values: ClientForm) => {
        const opts = {
            preserveScroll: true,
            onSuccess: () => {
                if (!editing) {
                    reset();
                }

                onOpenChange(false);
            },
        };

        if (editing && client) {
            router.patch(update(client.id).url, values, opts);
        } else {
            router.post(store().url, values, opts);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {editing ? 'Edit client' : 'Add a client'}
                    </DialogTitle>
                    <DialogDescription>
                        {editing
                            ? "Update the client's details."
                            : 'Add a client directly. They can access the portal once they pay or you send them access.'}
                    </DialogDescription>
                </DialogHeader>

                <form
                    id="client-form"
                    onSubmit={handleSubmit(submit)}
                    className="grid gap-4"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="client-name">Name</Label>
                        <Input id="client-name" {...register('name')} />
                        {errors.name && (
                            <p className="text-xs text-destructive">
                                {errors.name.message}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="client-email">Email</Label>
                        <Input
                            id="client-email"
                            type="email"
                            {...register('email')}
                        />
                        {errors.email && (
                            <p className="text-xs text-destructive">
                                {errors.email.message}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="client-company">Company</Label>
                            <Input
                                id="client-company"
                                {...register('company')}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="client-country">Country</Label>
                            <Controller
                                control={control}
                                name="country"
                                render={({ field }) => (
                                    <Select
                                        inputId="client-country"
                                        options={countries}
                                        isClearable
                                        classNamePrefix="rs"
                                        placeholder="Select…"
                                        value={
                                            countries.find(
                                                (c) => c.value === field.value,
                                            ) ?? null
                                        }
                                        onChange={(opt) =>
                                            field.onChange(
                                                (opt as SelectOption | null)
                                                    ?.value ?? '',
                                            )
                                        }
                                    />
                                )}
                            />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="client-phone">Phone (optional)</Label>
                        <Input id="client-phone" {...register('phone')} />
                    </div>
                </form>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        form="client-form"
                        disabled={isSubmitting}
                    >
                        {editing ? (
                            <Save className="size-4" />
                        ) : (
                            <UserPlus className="size-4" />
                        )}
                        {editing ? 'Save changes' : 'Add client'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
