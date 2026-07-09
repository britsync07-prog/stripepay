import { SetPriceCard } from '@/components/consultation/set-price-card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

/**
 * In-place editor for a priced custom lead's payment link: change the price,
 * title or expiry without leaving the consultation. Saving stays on the page
 * (the SetPriceCard posts with `stay`) and closes the dialog.
 */
export function ManagePaymentLinkDialog({
    open,
    onOpenChange,
    uuid,
    initial,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    uuid: string;
    initial: { title: string; amount: string; expires_in_days: number };
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Manage payment link</DialogTitle>
                    <DialogDescription>
                        Update the price, title or expiry. The link stays the
                        same — the client always pays the current amount.
                    </DialogDescription>
                </DialogHeader>

                <SetPriceCard
                    uuid={uuid}
                    initial={initial}
                    stay
                    bare
                    onDone={() => onOpenChange(false)}
                />
            </DialogContent>
        </Dialog>
    );
}
