import { usePage } from '@inertiajs/react';
import { Download } from 'lucide-react';

import { Button } from '@/components/ui/button';

/**
 * Downloads the current list as an .xlsx. It is a plain anchor (a real file
 * download, not an Inertia visit) and carries over the page's current query
 * string, so the export honours whatever search/status filters are applied.
 */
export function ExportButton({
    path,
    label = 'Export',
}: {
    path: string;
    label?: string;
}) {
    const { url } = usePage();
    const queryString = url.includes('?') ? url.slice(url.indexOf('?')) : '';

    return (
        <Button asChild variant="outline">
            <a href={`${path}${queryString}`}>
                <Download className="size-4" /> {label}
            </a>
        </Button>
    );
}
