<script lang="ts" setup>
import BoardDropdownMenu from '@/components/board/BoardDropdownMenu.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import WorkspaceAvatar from '@/components/workspace/WorkspaceAvatar.vue';
import { useInitials } from '@/composables/useInitials';
import { cn } from '@/lib/utils';
import type { Workspace, WorkspaceMember } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Plus, Star } from 'lucide-vue-next';
import { computed, nextTick, ref, useTemplateRef } from 'vue';

const MEMBER_AVATAR_CAP = 3;

const props = defineProps<{
    workspace: Workspace | null;
    workspaceName: string;
    workspaceHomeUrl: string;
    boardName: string;
    owner: WorkspaceMember;
    members: WorkspaceMember[];
    isStarred: boolean;
    /** Only the workspace owner can archive the board. */
    canArchive: boolean;
    /** Board members can leave the board; the owner can't. */
    canLeave: boolean;
    isArchiving: boolean;
    /** The board is archived: it can be read, and its members seen, but nothing changed. */
    isReadOnly?: boolean;
}>();

const emit = defineEmits<{
    rename: [name: string];
    toggleStar: [];
    addList: [];
    archiveBoard: [];
    leaveBoard: [];
    showArchivedItems: [];
    showMembers: [];
}>();

/*
|--------------------------------------------------------------------------
| Board name
|--------------------------------------------------------------------------
*/

const isEditingName = ref(false);
const draftName = ref(props.boardName);
const nameInput = useTemplateRef<HTMLInputElement>('board-name-input');
const nameButton = useTemplateRef<HTMLButtonElement>('board-name-button');

async function startEditingName() {
    if (props.isReadOnly) {
        return;
    }

    draftName.value = props.boardName;
    isEditingName.value = true;
    await nextTick();
    nameInput.value?.select();
}

/** Keyboard saves and cancels hand focus back to the name; a blur leaves it where the user clicked. */
async function stopEditingName(shouldRestoreFocus: boolean) {
    isEditingName.value = false;

    if (shouldRestoreFocus) {
        await nextTick();
        nameButton.value?.focus();
    }
}

function saveName(shouldRestoreFocus = false) {
    if (!isEditingName.value || props.isReadOnly) {
        return;
    }

    stopEditingName(shouldRestoreFocus);
    const name = draftName.value.trim();

    if (!name || name === props.boardName) {
        return;
    }

    emit('rename', name);
}

// The board page holds back server snapshots of the name while it is being edited.
defineExpose({ isEditingName });

/*
|--------------------------------------------------------------------------
| Members
|--------------------------------------------------------------------------
*/

const { getInitials } = useInitials();
const boardPeople = computed(() => [props.owner, ...props.members]);
const hiddenPeopleCount = computed(() => Math.max(boardPeople.value.length - MEMBER_AVATAR_CAP, 0));
// Keep the bubble the size of an avatar however big the board gets; the dialog lists everyone.
const hiddenPeopleLabel = computed(() => (hiddenPeopleCount.value > 99 ? '99+' : `+${hiddenPeopleCount.value}`));
const peopleLabel = computed(() => {
    const count = boardPeople.value.length;

    return `${count} ${count === 1 ? 'person' : 'people'} on this board`;
});
</script>

<template>
    <header class="flex items-center gap-3 border-b px-4 py-3 sm:px-6">
        <Link
            :aria-label="`${workspaceName} home`"
            :href="workspaceHomeUrl"
            class="shrink-0 rounded-lg outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
        >
            <WorkspaceAvatar
                :workspace="workspace ?? { name: workspaceName }"
                class="size-9 rounded-lg sm:size-11 sm:rounded-xl"
            />
        </Link>

        <div class="min-w-0 flex-1">
            <Link
                :href="workspaceHomeUrl"
                class="block max-w-full truncate text-xs font-medium text-muted-foreground transition-colors outline-none hover:text-foreground focus-visible:underline sm:text-sm"
            >
                {{ workspaceName }}
            </Link>
            <h1 v-if="isReadOnly" class="truncate text-lg leading-tight font-semibold tracking-tight sm:text-2xl">
                {{ boardName }}
            </h1>
            <h1 v-else-if="!isEditingName" class="flex min-w-0">
                <button
                    class="-mx-1.5 max-w-full cursor-pointer truncate rounded-md px-1.5 text-left text-lg leading-tight font-semibold tracking-tight transition-colors outline-none hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:text-2xl"
                    title="Rename board"
                    type="button"
                    ref="board-name-button"
                    @click="startEditingName"
                >
                    {{ boardName }}
                </button>
            </h1>
            <div v-else class="-mx-1.5 inline-grid max-w-full grid-cols-1">
                <span
                    aria-hidden="true"
                    class="invisible col-start-1 row-start-1 overflow-hidden px-1.5 text-lg leading-tight font-semibold tracking-tight whitespace-pre sm:text-2xl"
                    >{{ draftName || ' ' }}</span
                >
                <input
                    ref="board-name-input"
                    v-model="draftName"
                    aria-label="Board name"
                    class="col-start-1 row-start-1 w-full min-w-24 rounded-md border border-ring bg-background px-1.5 text-lg leading-tight font-semibold tracking-tight ring-[3px] ring-ring/50 outline-none sm:text-2xl"
                    maxlength="255"
                    @blur="saveName()"
                    @keydown.enter.prevent="saveName(true)"
                    @keydown.esc.prevent="stopEditingName(true)"
                />
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1">
            <button
                :aria-label="`Board members: ${peopleLabel}`"
                class="mr-1 hidden min-h-9 cursor-pointer items-center rounded-full p-0.5 transition-colors outline-none hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:flex"
                type="button"
                @click="emit('showMembers')"
            >
                <span aria-hidden="true" class="flex -space-x-2">
                    <Avatar
                        v-for="person in boardPeople.slice(0, MEMBER_AVATAR_CAP)"
                        :key="person.id"
                        class="size-7 ring-2 ring-background sm:size-8"
                    >
                        <AvatarImage :src="person.avatar ?? ''" alt="" class="object-cover" />
                        <AvatarFallback class="bg-blush text-xs font-semibold text-blush-foreground">
                            {{ getInitials(person.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <span
                        v-if="hiddenPeopleCount"
                        class="relative flex size-7 items-center justify-center rounded-full bg-muted text-[10px] font-semibold text-muted-foreground tabular-nums ring-2 ring-background sm:size-8 sm:text-xs"
                    >
                        {{ hiddenPeopleLabel }}
                    </span>
                </span>
            </button>
            <TooltipProvider v-if="!isReadOnly">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            :aria-label="isStarred ? 'Unstar board' : 'Star board'"
                            :aria-pressed="isStarred"
                            class="cursor-pointer"
                            size="icon"
                            variant="ghost"
                            @click="emit('toggleStar')"
                        >
                            <Star
                                :class="
                                    cn(
                                        'transition-colors',
                                        isStarred
                                            ? 'fill-current text-primary'
                                            : 'fill-transparent text-muted-foreground',
                                    )
                                "
                            />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>{{ isStarred ? 'Unstar' : 'Star' }} this board</TooltipContent>
                </Tooltip>
            </TooltipProvider>
            <Button
                v-if="!isReadOnly"
                class="hidden cursor-pointer sm:inline-flex"
                variant="outline"
                @click="emit('addList')"
            >
                <Plus />
                Add list
            </Button>
            <BoardDropdownMenu
                v-if="!isReadOnly"
                :can-archive="canArchive"
                :can-leave="canLeave"
                :is-archiving="isArchiving"
                :is-read-only="isReadOnly"
                @add-list="emit('addList')"
                @archive-board="emit('archiveBoard')"
                @leave-board="emit('leaveBoard')"
                @show-archived-items="emit('showArchivedItems')"
                @show-members="emit('showMembers')"
            />
        </div>
    </header>
</template>
