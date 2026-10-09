<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { formatTimeAgo } from '@/lib/relativeTime';
import boardRoutes from '@/routes/boards';
import type { ArchivedBoardList, ArchivedItems, DeletedCard } from '@/types';
import { useHttp, usePage } from '@inertiajs/vue3';
import { Archive, ArchiveRestore, CircleAlert, Search } from 'lucide-vue-next';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';

const SEARCH_THRESHOLD = 5;

const props = defineProps<{
    boardId: string;
    boardName: string;
}>();

const emit = defineEmits<{
    restoreList: [list: ArchivedBoardList];
    restoreCard: [card: DeletedCard];
}>();

const open = defineModel<boolean>('open', { default: false });

const page = usePage();
const archivedItemsRequest = useHttp();

const archivedItems = ref<ArchivedItems | null>(null);
const hasLoadFailed = ref(false);
const search = ref('');
const announcement = ref('');
const content = useTemplateRef<HTMLElement>('content');

const itemCount = computed(
    () => (archivedItems.value?.board_lists.length ?? 0) + (archivedItems.value?.cards.length ?? 0),
);
const showSearch = computed(() => itemCount.value > SEARCH_THRESHOLD);
const query = computed(() => search.value.trim().toLowerCase());

const filteredLists = computed(() =>
    (archivedItems.value?.board_lists ?? []).filter((list) => list.name.toLowerCase().includes(query.value)),
);
const filteredCards = computed(() =>
    (archivedItems.value?.cards ?? []).filter((card) => card.name.toLowerCase().includes(query.value)),
);

/** Fetch what's archived. A failed refresh keeps showing what was already loaded. */
function load() {
    hasLoadFailed.value = false;

    archivedItemsRequest
        .get(boardRoutes.archivedItems(props.boardId).url, {
            onSuccess: (data) => {
                archivedItems.value = data as ArchivedItems;
            },
            onHttpException: () => (hasLoadFailed.value = archivedItems.value === null),
            onNetworkError: () => (hasLoadFailed.value = archivedItems.value === null),
        })
        .catch(() => {});
}

function describeList(list: ArchivedBoardList) {
    const archiver = list.archiver?.id === page.props.auth.user.id ? 'you' : list.archiver?.name;
    return `Archived ${formatTimeAgo(list.archived_at)}${archiver ? ` by ${archiver}` : ''}`;
}

function describeCard(card: DeletedCard) {
    return `Deleted ${formatTimeAgo(card.deleted_at)} · from ${card.board_list.name}`;
}

function announce(message: string) {
    announcement.value = '';
    nextTick(() => (announcement.value = message));
}

/** Take the row out and keep keyboard focus in the sheet, on the next restore button or the list itself. */
async function removeRow<T extends { id: string }>(items: T[], item: T) {
    const restoreButtons = Array.from(
        content.value?.querySelectorAll<HTMLElement>(
            '.archived-item:not(.archived-item-leave-active) [data-restore]',
        ) ?? [],
    );
    const position = restoreButtons.findIndex((button) => button.dataset.restore === item.id);

    items.splice(items.indexOf(item), 1);
    await nextTick();

    const remainingButtons = content.value?.querySelectorAll<HTMLElement>(
        '.archived-item:not(.archived-item-leave-active) [data-restore]',
    );
    const nextFocus = remainingButtons?.[Math.min(Math.max(position, 0), remainingButtons.length - 1)];
    (nextFocus ?? content.value)?.focus();
}

function restoreList(list: ArchivedBoardList) {
    if (!archivedItems.value) {
        return;
    }

    removeRow(archivedItems.value.board_lists, list);
    emit('restoreList', list);
    announce(`Restored list ${list.name}.`);
}

function restoreCard(card: DeletedCard) {
    if (!archivedItems.value) {
        return;
    }

    removeRow(archivedItems.value.cards, card);
    emit('restoreCard', card);
    announce(`Restored card ${card.name}.`);
}

watch(open, (isOpen) => {
    if (isOpen) {
        search.value = '';
        load();
    } else {
        archivedItemsRequest.cancel();
    }
});

// The board calls this when a restore fails, so the sheet shows what is really archived again.
defineExpose({ reload: load });
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent class="w-full gap-0 p-0 sm:max-w-md">
            <SheetHeader class="gap-1.5 border-b px-5 pt-5 pr-12 pb-4 sm:px-6">
                <SheetTitle class="text-xl tracking-tight">Archived items</SheetTitle>
                <SheetDescription
                    >Restore archived lists and deleted cards to put them back on {{ boardName }}.</SheetDescription
                >
                <div v-if="showSearch" class="relative mt-2">
                    <Search
                        aria-hidden="true"
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        aria-label="Search archived items"
                        class="pl-9"
                        placeholder="Search by name"
                        type="search"
                    />
                </div>
            </SheetHeader>

            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-4 sm:px-6">
                <Empty v-if="hasLoadFailed" class="p-0 py-4">
                    <EmptyHeader>
                        <EmptyMedia class="bg-destructive/10 text-destructive" variant="icon">
                            <CircleAlert />
                        </EmptyMedia>
                        <EmptyTitle>Couldn’t load archived items</EmptyTitle>
                        <EmptyDescription>Check your connection, then try again.</EmptyDescription>
                    </EmptyHeader>
                    <EmptyContent>
                        <Button
                            :disabled="archivedItemsRequest.processing"
                            class="cursor-pointer"
                            size="sm"
                            variant="outline"
                            @click="load"
                        >
                            {{ archivedItemsRequest.processing ? 'Trying again…' : 'Try again' }}
                        </Button>
                    </EmptyContent>
                </Empty>

                <div v-else-if="archivedItems === null" aria-busy="true" class="flex flex-col gap-2">
                    <span class="sr-only">Loading archived items</span>
                    <Skeleton class="mb-1 h-4 w-16" />
                    <div v-for="i in 3" :key="i" class="flex items-center gap-3 rounded-xl border p-3">
                        <div class="flex flex-1 flex-col gap-2">
                            <Skeleton :class="i % 2 ? 'w-2/3' : 'w-1/2'" class="h-3.5" />
                            <Skeleton class="h-3 w-1/3" />
                        </div>
                        <Skeleton class="h-8 w-20 rounded-md" />
                    </div>
                </div>

                <Empty v-else-if="!itemCount" class="p-0 py-4">
                    <EmptyHeader>
                        <EmptyMedia class="bg-blush text-blush-foreground" variant="icon">
                            <Archive />
                        </EmptyMedia>
                        <EmptyTitle>Nothing archived</EmptyTitle>
                        <EmptyDescription>
                            Lists you archive and cards you delete wait here until you restore them.
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>

                <div v-else ref="content" class="space-y-6 outline-none" tabindex="-1">
                    <p
                        v-if="query && !filteredLists.length && !filteredCards.length"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        Nothing archived matches “{{ search.trim() }}”.
                    </p>

                    <section v-if="filteredLists.length" aria-labelledby="archived-lists-heading">
                        <h3 id="archived-lists-heading" class="mb-2 text-sm font-medium text-muted-foreground">
                            Lists
                        </h3>
                        <TransitionGroup class="relative space-y-2" name="archived-item" tag="ul">
                            <li
                                v-for="list in filteredLists"
                                :key="list.id"
                                class="archived-item flex items-center gap-3 rounded-xl border bg-card p-3"
                            >
                                <span
                                    :class="list.color ? `list-${list.color}` : 'list-default'"
                                    aria-hidden="true"
                                    class="size-3 shrink-0 rounded-full ring-1 ring-black/10 [background:var(--list-bg)] dark:ring-white/15"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ list.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ describeList(list) }}</p>
                                </div>
                                <Button
                                    :data-restore="list.id"
                                    class="shrink-0 cursor-pointer"
                                    size="sm"
                                    variant="outline"
                                    @click="restoreList(list)"
                                >
                                    <ArchiveRestore aria-hidden="true" />
                                    Restore<span class="sr-only"> list {{ list.name }}</span>
                                </Button>
                            </li>
                        </TransitionGroup>
                    </section>

                    <section v-if="filteredCards.length" aria-labelledby="deleted-cards-heading">
                        <h3 id="deleted-cards-heading" class="mb-2 text-sm font-medium text-muted-foreground">Cards</h3>
                        <TransitionGroup class="relative space-y-2" name="archived-item" tag="ul">
                            <li
                                v-for="card in filteredCards"
                                :key="card.id"
                                class="archived-item flex items-center gap-3 rounded-xl border bg-card p-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ card.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ describeCard(card) }}</p>
                                </div>
                                <Button
                                    :data-restore="card.id"
                                    class="shrink-0 cursor-pointer"
                                    size="sm"
                                    variant="outline"
                                    @click="restoreCard(card)"
                                >
                                    <ArchiveRestore aria-hidden="true" />
                                    Restore<span class="sr-only"> card {{ card.name }}</span>
                                </Button>
                            </li>
                        </TransitionGroup>
                    </section>
                </div>
            </div>

            <p aria-live="polite" class="sr-only">{{ announcement }}</p>
        </SheetContent>
    </Sheet>
</template>

<style scoped>
.archived-item-move,
.archived-item-enter-active,
.archived-item-leave-active {
    transition:
        transform 320ms cubic-bezier(0.22, 1, 0.36, 1),
        opacity 240ms ease,
        background-color 200ms ease,
        border-color 200ms ease;
}

.archived-item-leave-active {
    position: absolute;
    inset-inline: 0;
    pointer-events: none;
    border-color: color-mix(in oklab, var(--primary) 45%, transparent);
    background-color: color-mix(in oklab, var(--primary) 8%, var(--card));
}

.archived-item-enter-from {
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
}

.archived-item-leave-to {
    opacity: 0;
    transform: translateX(2rem);
}

@media (prefers-reduced-motion: reduce) {
    .archived-item-move,
    .archived-item-enter-active,
    .archived-item-leave-active {
        transition: opacity 150ms ease;
    }

    .archived-item-enter-from,
    .archived-item-leave-to {
        transform: none;
    }
}
</style>
