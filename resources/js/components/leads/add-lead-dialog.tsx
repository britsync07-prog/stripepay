import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
import { Controller, useForm } from 'react-hook-form';
import Select from 'react-select';
import AsyncSelect from 'react-select/async';
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
import { Textarea } from '@/components/ui/textarea';
import { search as searchContacts } from '@/routes/admin/contacts';
import { store } from '@/routes/admin/leads';
import type { SelectOption } from '@/types';

type ContactRow = {
    uuid: string;
    name: string;
    email: string;
    company: string | null;
    country: string | null;
};

type ContactOption = { value: string; label: string; contact: ContactRow };

async function loadContacts(input: string): Promise<ContactOption[]> {
    if (input.trim().length < 2) {
        return [];
    }

    const res = await fetch(
        `${searchContacts().url}?q=${encodeURIComponent(input)}`,
        { headers: { Accept: 'application/json' }, credentials: 'same-origin' },
    );

    if (!res.ok) {
        return [];
    }

    const data: { contacts: ContactRow[] } = await res.json();

    return data.contacts.map((c) => ({
        value: c.uuid,
        label: `${c.name} · ${c.email}`,
        contact: c,
    }));
}

const leadSchema = z.object({
    // Name + email are optional — the client can fill them in via the link.
    name: z.string().max(255).optional().or(z.literal('')),
    email: z
        .string()
        .email('Enter a valid email')
        .max(255)
        .optional()
        .or(z.literal('')),
    company: z.string().max(255).optional().or(z.literal('')),
    country: z.string().length(2).optional().or(z.literal('')),
    message: z.string().max(5000).optional().or(z.literal('')),
    next_follow_up_at: z.string().optional().or(z.literal('')),
});

type LeadForm = z.infer<typeof leadSchema>;

export function AddLeadDialog({
    open,
    onOpenChange,
    countries,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    countries: SelectOption[];
}) {
    const {
        register,
        handleSubmit,
        control,
        reset,
        setValue,
        formState: { errors, isSubmitting },
    } = useForm<LeadForm>({
        resolver: zodResolver(leadSchema),
        defaultValues: {
            name: '',
            email: '',
            company: '',
            country: '',
            message: '',
            next_follow_up_at: '',
        },
    });

    const submit = (values: LeadForm) => {
        router.post(store().url, values, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Add a lead</DialogTitle>
                    <DialogDescription>
                        Track a prospect you spoke to. Name and email are
                        optional — if you don't know them yet, the client fills
                        them in themselves via the quotation link.
                    </DialogDescription>
                </DialogHeader>

                <form
                    id="add-lead"
                    onSubmit={handleSubmit(submit)}
                    className="grid gap-4"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="lead-contact">
                            Find an existing client or contact (optional)
                        </Label>
                        <AsyncSelect<ContactOption>
                            inputId="lead-contact"
                            cacheOptions
                            defaultOptions={false}
                            loadOptions={loadContacts}
                            isClearable
                            classNamePrefix="rs"
                            placeholder="Search by name, email or company…"
                            noOptionsMessage={({ inputValue }) =>
                                inputValue.trim().length < 2
                                    ? 'Type to search…'
                                    : 'No match — enter the details below'
                            }
                            onChange={(opt) => {
                                const c = opt?.contact;

                                if (!c) {
                                    return;
                                }

                                setValue('name', c.name, {
                                    shouldValidate: true,
                                });
                                setValue('email', c.email, {
                                    shouldValidate: true,
                                });
                                setValue('company', c.company ?? '');
                                setValue('country', c.country ?? '');
                            }}
                        />
                        <p className="text-xs text-muted-foreground">
                            Pick someone you already know to prefill their
                            details, or just type a new person below.
                        </p>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="lead-name">
                            Name{' '}
                            <span className="text-muted-foreground">
                                (optional)
                            </span>
                        </Label>
                        <Input id="lead-name" {...register('name')} />
                        {errors.name && (
                            <p className="text-xs text-destructive">
                                {errors.name.message}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="lead-email">
                            Email{' '}
                            <span className="text-muted-foreground">
                                (optional)
                            </span>
                        </Label>
                        <Input
                            id="lead-email"
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
                            <Label htmlFor="lead-company">Company</Label>
                            <Input id="lead-company" {...register('company')} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="lead-country">Country</Label>
                            <Controller
                                control={control}
                                name="country"
                                render={({ field }) => (
                                    <Select
                                        inputId="lead-country"
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
                        <Label htmlFor="lead-followup">
                            Next follow-up (optional)
                        </Label>
                        <Input
                            id="lead-followup"
                            type="date"
                            {...register('next_follow_up_at')}
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="lead-message">Notes</Label>
                        <Textarea
                            id="lead-message"
                            rows={3}
                            {...register('message')}
                        />
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
                        form="add-lead"
                        disabled={isSubmitting}
                    >
                        <UserPlus className="size-4" />
                        Add lead
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
