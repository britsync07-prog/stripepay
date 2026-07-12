import { zodResolver } from '@hookform/resolvers/zod';
import { router, usePage } from '@inertiajs/react';
import { Link2 } from 'lucide-react';
import { useForm } from 'react-hook-form';
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
import { store } from '@/routes/admin/payment-links';

const schema = z.object({
    amount: z
        .string()
        .min(1, 'Enter an amount')
        .refine((v) => Number(v) > 0, 'Enter an amount greater than zero'),
    title: z.string().max(255).optional().or(z.literal('')),
    expires_in_days: z.string().optional().or(z.literal('')),
});

type FormValues = z.infer<typeof schema>;

export function CreatePaymentLinkDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { currency } = usePage().props;

    const {
        register,
        handleSubmit,
        reset,
        formState: { errors, isSubmitting },
    } = useForm<FormValues>({
        resolver: zodResolver(schema),
        defaultValues: { amount: '', title: '', expires_in_days: '3' },
    });

    const submit = (values: FormValues) => {
        router.post(
            store().url,
            {
                amount: values.amount,
                title: values.title || undefined,
                expires_in_days: values.expires_in_days || undefined,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    reset();
                    onOpenChange(false);
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>New payment link</DialogTitle>
                    <DialogDescription>
                        Set a price and share the secure link. Your client fills
                        in their own details at checkout — no account needed.
                    </DialogDescription>
                </DialogHeader>

                <form
                    id="new-payment-link"
                    onSubmit={handleSubmit(submit)}
                    className="grid gap-4"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="pl-amount">
                            Amount ({currency.code})
                        </Label>
                        <div className="relative">
                            <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground">
                                {currency.symbol}
                            </span>
                            <Input
                                id="pl-amount"
                                type="number"
                                step="0.01"
                                min="1"
                                inputMode="decimal"
                                placeholder="100"
                                className="pl-7"
                                autoFocus
                                {...register('amount')}
                            />
                        </div>
                        {errors.amount && (
                            <p className="text-xs text-destructive">
                                {errors.amount.message}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="pl-title">Label (optional)</Label>
                        <Input
                            id="pl-title"
                            placeholder="Consultation"
                            {...register('title')}
                        />
                        <p className="text-xs text-muted-foreground">
                            Shown to the client on the checkout page.
                        </p>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="pl-expiry">Expires in (days)</Label>
                        <Input
                            id="pl-expiry"
                            type="number"
                            min="1"
                            max="365"
                            placeholder="3"
                            {...register('expires_in_days')}
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
                        form="new-payment-link"
                        disabled={isSubmitting}
                    >
                        <Link2 className="size-4" />
                        Create link
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
