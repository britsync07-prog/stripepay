import {
    closestCenter,
    DndContext,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import type { DragEndEvent } from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Head, Link, router } from '@inertiajs/react';
import {
    Check,
    GripVertical,
    LayoutList,
    Pencil,
    Sparkles,
} from 'lucide-react';
import { useState } from 'react';

import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Reveal } from '@/components/reveal';
import { RowActionLink, RowActions } from '@/components/row-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { create, edit, reorder } from '@/routes/admin/plans';
import type { Plan } from '@/types';

function PlanCard({ plan }: { plan: Plan }) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: plan.id! });

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={cn(
                'group flex items-center gap-3 surface-card p-4 transition-shadow sm:gap-4 sm:p-5',
                isDragging
                    ? 'z-10 border-primary/40 shadow-elevated'
                    : 'hover:shadow-elevated',
                plan.is_featured && 'ring-1 ring-primary/20',
            )}
        >
            <button
                type="button"
                aria-label="Drag to reorder"
                className="-ml-1 flex size-8 shrink-0 cursor-grab touch-none items-center justify-center rounded-md text-muted-foreground/60 transition-colors hover:bg-muted hover:text-foreground active:cursor-grabbing"
                {...attributes}
                {...listeners}
            >
                <GripVertical className="size-4" />
            </button>

            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                    <h3 className="truncate font-serif text-lg font-semibold">
                        {plan.name}
                    </h3>
                    {plan.is_featured && (
                        <Badge className="gap-1">
                            <Sparkles className="size-3" /> Featured
                        </Badge>
                    )}
                    {plan.is_active ? (
                        <Badge variant="success">Active</Badge>
                    ) : (
                        <Badge variant="secondary">Hidden</Badge>
                    )}
                </div>
                <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-xs text-muted-foreground">
                    <span>{plan.slug}</span>
                    <span className="inline-flex items-center gap-1">
                        <Check className="size-3" />
                        {plan.features.length}{' '}
                        {plan.features.length === 1 ? 'feature' : 'features'}
                    </span>
                </div>
            </div>

            <div className="shrink-0 text-right">
                <div className="font-mono text-lg font-bold tracking-tight tabular-nums">
                    {plan.formatted_price}
                </div>
                <div className="text-xs text-muted-foreground">
                    {plan.interval_label}
                </div>
            </div>

            <RowActions className="shrink-0">
                <RowActionLink
                    href={edit(plan.id!)}
                    icon={Pencil}
                    label="Edit plan"
                />
            </RowActions>
        </div>
    );
}

export default function AdminPlans({ plans }: { plans: Plan[] }) {
    const [items, setItems] = useState(plans);

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const onDragEnd = ({ active, over }: DragEndEvent) => {
        if (!over || active.id === over.id) {
            return;
        }

        setItems((prev) => {
            const oldIndex = prev.findIndex((p) => p.id === active.id);
            const newIndex = prev.findIndex((p) => p.id === over.id);
            const next = arrayMove(prev, oldIndex, newIndex);

            router.post(
                reorder().url,
                { ids: next.map((p) => p.id) },
                { preserveScroll: true, preserveState: true },
            );

            return next;
        });
    };

    return (
        <>
            <Head title="Plans" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow="Catalog"
                    title="Plans"
                    description="Drag to reorder how plans appear on the public site."
                    actions={
                        <Button asChild>
                            <Link href={create()}>
                                <LayoutList className="size-4" /> Add plan
                            </Link>
                        </Button>
                    }
                />

                {items.length === 0 ? (
                    <Reveal className="rounded-2xl border border-dashed">
                        <EmptyState
                            icon={LayoutList}
                            title="No plans yet"
                            description="Create your first plan to offer it on the public site."
                            action={
                                <Button asChild>
                                    <Link href={create()}>
                                        <LayoutList className="size-4" /> Add
                                        plan
                                    </Link>
                                </Button>
                            }
                        />
                    </Reveal>
                ) : (
                    <DndContext
                        sensors={sensors}
                        collisionDetection={closestCenter}
                        onDragEnd={onDragEnd}
                    >
                        <SortableContext
                            items={items.map((p) => p.id!)}
                            strategy={verticalListSortingStrategy}
                        >
                            <div className="flex flex-col gap-3">
                                {items.map((plan) => (
                                    <PlanCard key={plan.id} plan={plan} />
                                ))}
                            </div>
                        </SortableContext>
                    </DndContext>
                )}
            </div>
        </>
    );
}
