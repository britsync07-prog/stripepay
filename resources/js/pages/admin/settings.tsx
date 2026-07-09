import { Head, useForm } from '@inertiajs/react';
import { Building2, Receipt } from 'lucide-react';

import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Panel } from '@/components/panel';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { update as updateSettings } from '@/routes/admin/settings';

interface Settings {
    currency_code: string;
    currency_symbol: string;
    tax_rate: number;
    company_name: string;
    company_email: string | null;
    company_address: string | null;
    vat_number: string | null;
    invoice_footer: string | null;
    signatory_name: string | null;
    signatory_title: string | null;
}

interface CurrencyOption {
    value: string;
    label: string;
    symbol: string;
}

export default function AdminSettings({
    settings,
    currencies,
}: {
    settings: Settings;
    currencies: CurrencyOption[];
}) {
    const form = useForm({
        currency_code: settings.currency_code,
        tax_rate: settings.tax_rate,
        company_name: settings.company_name,
        company_email: settings.company_email ?? '',
        company_address: settings.company_address ?? '',
        vat_number: settings.vat_number ?? '',
        invoice_footer: settings.invoice_footer ?? '',
        signatory_name: settings.signatory_name ?? '',
        signatory_title: settings.signatory_title ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.patch(updateSettings().url, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Platform settings" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Configuration"
                    title="Platform settings"
                    description="Operating currency, tax treatment, and the company details shown on invoices."
                />
                <form onSubmit={submit} className="grid gap-6">
                    <Panel
                        eyebrow="Money"
                        title="Currency"
                        description="The single operating currency. Set this once at setup — changing it later does not re-convert amounts already stored."
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="currency_code">Currency</Label>
                            <Select
                                value={form.data.currency_code}
                                onValueChange={(v) =>
                                    form.setData('currency_code', v)
                                }
                            >
                                <SelectTrigger
                                    id="currency_code"
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Select a currency" />
                                </SelectTrigger>
                                <SelectContent>
                                    {currencies.map((c) => (
                                        <SelectItem
                                            key={c.value}
                                            value={c.value}
                                        >
                                            {c.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.currency_code} />
                        </div>
                    </Panel>

                    <Panel
                        eyebrow="Tax"
                        title="Sales tax"
                        description="Applied to invoices going forward. Prices are treated as tax-inclusive, so the tax is broken out of the amount charged — the receipt total never changes."
                    >
                        <div className="grid gap-2 sm:max-w-xs">
                            <Label htmlFor="tax_rate">
                                <Receipt className="mr-1 inline size-3.5" />
                                Tax rate (%)
                            </Label>
                            <Input
                                id="tax_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                value={form.data.tax_rate}
                                onChange={(e) =>
                                    form.setData(
                                        'tax_rate',
                                        Number(e.target.value),
                                    )
                                }
                            />
                            <InputError message={form.errors.tax_rate} />
                        </div>
                    </Panel>

                    <Panel
                        eyebrow="Company"
                        title="Company & invoices"
                        description="Shown on invoices and receipts."
                    >
                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="company_name">
                                    <Building2 className="mr-1 inline size-3.5" />
                                    Company name
                                </Label>
                                <Input
                                    id="company_name"
                                    value={form.data.company_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'company_name',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.company_name}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="company_email">
                                    Billing email
                                </Label>
                                <Input
                                    id="company_email"
                                    type="email"
                                    value={form.data.company_email}
                                    onChange={(e) =>
                                        form.setData(
                                            'company_email',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.company_email}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="vat_number">
                                    Tax / VAT number
                                </Label>
                                <Input
                                    id="vat_number"
                                    value={form.data.vat_number}
                                    onChange={(e) =>
                                        form.setData(
                                            'vat_number',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.vat_number} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="company_address">Address</Label>
                                <Textarea
                                    id="company_address"
                                    rows={3}
                                    value={form.data.company_address}
                                    onChange={(e) =>
                                        form.setData(
                                            'company_address',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.company_address}
                                />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="invoice_footer">
                                    Invoice footer note
                                </Label>
                                <Textarea
                                    id="invoice_footer"
                                    rows={2}
                                    value={form.data.invoice_footer}
                                    onChange={(e) =>
                                        form.setData(
                                            'invoice_footer',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g. Thank you for your business."
                                />
                                <InputError
                                    message={form.errors.invoice_footer}
                                />
                            </div>
                        </div>
                    </Panel>

                    <Panel
                        eyebrow="Quotations"
                        title="Signatory"
                        description="The name and title shown as “Prepared by” on quotations."
                    >
                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="signatory_name">
                                    Full name
                                </Label>
                                <Input
                                    id="signatory_name"
                                    value={form.data.signatory_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'signatory_name',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g. Fadoua Fakir"
                                />
                                <InputError
                                    message={form.errors.signatory_name}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="signatory_title">Title</Label>
                                <Input
                                    id="signatory_title"
                                    value={form.data.signatory_title}
                                    onChange={(e) =>
                                        form.setData(
                                            'signatory_title',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g. Managing Director"
                                />
                                <InputError
                                    message={form.errors.signatory_title}
                                />
                            </div>
                        </div>
                    </Panel>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner className="mr-2" />}
                            Save settings
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}
