<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useInitials } from '@/composables/useInitials';
import { useWorkspaceSync } from '@/composables/useWorkspaceSync';
import AppLayout from '@/layouts/AppLayout.vue';
import { IMAGE_UPLOAD_MAX_BYTES, IMAGE_UPLOAD_TYPES } from '@/lib/imageUpload';
import { dashboard } from '@/routes';
import workspaceRoutes from '@/routes/workspaces';
import type { BreadcrumbItem, Workspace } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Camera, Loader2, Trash2, TriangleAlert } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const NAME_MAX_LENGTH = 65;
const DESCRIPTION_MAX_LENGTH = 255;

const props = defineProps<{
    workspace: Workspace;
    boardCount: number;
    memberCount: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Workspace', href: workspaceRoutes.home(props.workspace.id).url },
    { title: 'Settings', href: workspaceRoutes.settings(props.workspace.id).url },
];

useWorkspaceSync({ workspaceId: props.workspace.id, boardProps: ['boardCount'], workspaceProps: ['memberCount'] });

const { getInitials } = useInitials();

const form = useForm({
    name: props.workspace.name,
    description: props.workspace.description ?? '',
    logo: null as File | null,
    remove_logo: false,
});

const fileInput = ref<HTMLInputElement | null>(null);
const pendingLogoUrl = ref<string | null>(null);
const isDraggingLogo = ref(false);
const announcement = ref('');

const showDeleteDialog = ref(false);
const deleteConfirmation = ref('');
const isDeleting = ref(false);

const previewUrl = computed(() => pendingLogoUrl.value ?? (form.remove_logo ? null : props.workspace.logo || null));

const isDeleteConfirmed = computed(() => deleteConfirmation.value.trim() === props.workspace.name);

const deleteConsequences = computed(() => {
    const boards = `${props.boardCount} ${props.boardCount === 1 ? 'board' : 'boards'}`;
    const members = `${props.memberCount} ${props.memberCount === 1 ? 'member' : 'members'}`;

    return props.memberCount
        ? `Its ${boards}, with every list and card in them, are deleted and ${members} lose access.`
        : `Its ${boards}, with every list and card in them, are deleted.`;
});

watch(showDeleteDialog, (isOpen) => {
    if (!isOpen) {
        deleteConfirmation.value = '';
    }
});

function announce(message: string) {
    announcement.value = '';
    nextTick(() => (announcement.value = message));
}

function setPendingLogo(file: File | null) {
    if (pendingLogoUrl.value) {
        URL.revokeObjectURL(pendingLogoUrl.value);
    }

    pendingLogoUrl.value = file ? URL.createObjectURL(file) : null;
    form.logo = file;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function chooseLogo() {
    fileInput.value?.click();
}

function selectLogo(file: File) {
    if (!IMAGE_UPLOAD_TYPES.includes(file.type)) {
        form.setError('logo', 'Choose a JPG, PNG, GIF or WebP image.');

        return;
    }

    if (file.size > IMAGE_UPLOAD_MAX_BYTES) {
        form.setError('logo', 'Choose an image that is 2 MB or smaller.');

        return;
    }

    form.clearErrors('logo');
    form.remove_logo = false;
    setPendingLogo(file);
}

function onLogoInputChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (file) {
        selectLogo(file);
    }
}

function onLogoDrop(event: DragEvent) {
    isDraggingLogo.value = false;
    const file = event.dataTransfer?.files?.[0];

    if (file) {
        selectLogo(file);
    }
}

function removeLogo() {
    form.clearErrors('logo');
    setPendingLogo(null);
    form.remove_logo = Boolean(props.workspace.logo);
}

function discardChanges() {
    setPendingLogo(null);
    form.reset();
    form.clearErrors();
}

function focusFirstInvalidField(errors: Record<string, string>) {
    const fieldId = ['name', 'description', 'logo'].find((field) => field in errors);

    if (fieldId) {
        nextTick(() => document.getElementById(fieldId === 'logo' ? 'logo-button' : fieldId)?.focus());
    }
}

function handleSaveFailure(status?: number) {
    if (status === 404) {
        toast.error('This workspace no longer exists');
        router.visit(dashboard());

        return;
    }

    const descriptions: Record<number, string> = {
        403: 'Only the workspace owner can change its settings.',
        413: 'That image is too large to upload. Choose one that is 2 MB or smaller.',
        419: 'Your session expired. Refresh the page, then try again.',
    };
    const canRetry = status === undefined || !(status in descriptions);

    toast.error('Couldn’t save your changes', {
        description:
            status === undefined
                ? 'Check your connection, then try again.'
                : (descriptions[status] ?? 'Something went wrong on our end. Try again in a moment.'),
        action: canRetry ? { label: 'Try again', onClick: save } : undefined,
    });
}

function save() {
    form.transform((data) => ({ ...data, _method: 'patch' })).post(workspaceRoutes.update(props.workspace.id).url, {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        only: ['workspace', 'ownedWorkspaces'],
        onSuccess: () => {
            setPendingLogo(null);
            form.defaults({ name: form.name, description: form.description, logo: null, remove_logo: false });
            form.reset('logo', 'remove_logo');
            announce('Workspace settings saved.');
        },
        onError: focusFirstInvalidField,
        onHttpException: (response) => {
            handleSaveFailure(response.status);

            return false;
        },
        onNetworkError: () => {
            handleSaveFailure();

            return false;
        },
    });
}

function handleDeleteFailure(status?: number) {
    if (status === 404) {
        toast.error(`${props.workspace.name} was already deleted`);
        router.visit(dashboard());

        return;
    }

    const descriptions: Record<number, string> = {
        403: 'Only the workspace owner can delete it.',
        419: 'Your session expired. Refresh the page, then try again.',
    };
    const canRetry = status === undefined || !(status in descriptions);

    if (!canRetry) {
        showDeleteDialog.value = false;
    }

    toast.error(`Couldn’t delete ${props.workspace.name}`, {
        description:
            status === undefined
                ? 'Check your connection, then try again.'
                : (descriptions[status] ?? 'Something went wrong on our end. Try again in a moment.'),
    });
}

function deleteWorkspace() {
    if (!isDeleteConfirmed.value || isDeleting.value) {
        return;
    }

    const workspaceName = props.workspace.name;

    router.delete(workspaceRoutes.destroy(props.workspace.id).url, {
        onStart: () => (isDeleting.value = true),
        onSuccess: () => toast.success(`Deleted ${workspaceName}`),
        onHttpException: (response) => {
            handleDeleteFailure(response.status);

            return false;
        },
        onNetworkError: () => {
            handleDeleteFailure();

            return false;
        },
        onFinish: () => (isDeleting.value = false),
    });
}

onBeforeUnmount(() => {
    if (pendingLogoUrl.value) {
        URL.revokeObjectURL(pendingLogoUrl.value);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`${workspace.name} settings`" />

        <div class="mx-auto w-full max-w-3xl px-4 pt-8 pb-16 sm:px-6 sm:pt-10 lg:px-10">
            <header>
                <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Settings</h1>
                <p class="mt-1.5 max-w-prose text-sm text-muted-foreground">
                    Change how <span class="font-medium text-foreground">{{ workspace.name }}</span> looks to everyone
                    in it.
                </p>
            </header>

            <section aria-labelledby="details-heading" class="mt-10">
                <h2 id="details-heading" class="mb-6 border-b pb-3 text-xl font-semibold tracking-tight sm:text-2xl">
                    Details
                </h2>

                <form class="space-y-7" novalidate @submit.prevent="save">
                    <div>
                        <span id="logo-label" class="text-sm font-medium">Logo</span>
                        <div class="mt-2 flex flex-wrap items-center gap-4 sm:gap-5">
                            <button
                                id="logo-button"
                                :aria-describedby="form.errors.logo ? 'logo-error' : 'logo-hint'"
                                :aria-invalid="Boolean(form.errors.logo)"
                                :class="
                                    isDraggingLogo
                                        ? 'scale-105 ring-2 ring-primary'
                                        : 'ring-1 ring-border hover:ring-primary/40'
                                "
                                aria-label="Choose a logo image"
                                class="group relative size-20 shrink-0 cursor-pointer overflow-hidden rounded-2xl transition-[transform,box-shadow] duration-200 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 motion-reduce:transition-none sm:size-24"
                                type="button"
                                @click="chooseLogo"
                                @dragleave="isDraggingLogo = false"
                                @dragover.prevent="isDraggingLogo = true"
                                @drop.prevent="onLogoDrop"
                            >
                                <Avatar class="size-full rounded-2xl">
                                    <AvatarImage :src="previewUrl ?? ''" alt="" class="object-cover" />
                                    <AvatarFallback
                                        class="rounded-2xl bg-blush text-2xl font-semibold text-blush-foreground"
                                    >
                                        {{ getInitials(form.name || workspace.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <span
                                    aria-hidden="true"
                                    class="absolute inset-0 flex items-center justify-center bg-black/45 opacity-0 transition-opacity duration-200 group-hover:opacity-100 group-focus-visible:opacity-100"
                                >
                                    <Camera class="size-5 text-white" />
                                </span>
                            </button>

                            <div class="min-w-0 space-y-2">
                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        class="cursor-pointer"
                                        size="sm"
                                        type="button"
                                        variant="outline"
                                        @click="chooseLogo"
                                    >
                                        <Camera aria-hidden="true" />
                                        {{ previewUrl ? 'Replace logo' : 'Upload logo' }}
                                    </Button>
                                    <Button
                                        v-if="previewUrl"
                                        class="cursor-pointer text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                        size="sm"
                                        type="button"
                                        variant="ghost"
                                        @click="removeLogo"
                                    >
                                        <Trash2 aria-hidden="true" />
                                        Remove<span class="sr-only"> logo</span>
                                    </Button>
                                </div>
                                <p id="logo-hint" class="text-xs text-muted-foreground">
                                    JPG, PNG, GIF or WebP, up to 2 MB. Drop an image on the logo to replace it.
                                </p>
                                <InputError id="logo-error" :message="form.errors.logo" role="alert" />
                            </div>
                        </div>

                        <input
                            ref="fileInput"
                            :accept="IMAGE_UPLOAD_TYPES.join(',')"
                            aria-labelledby="logo-label"
                            class="sr-only"
                            tabindex="-1"
                            type="file"
                            @change="onLogoInputChange"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">
                            Name
                            <span aria-hidden="true" class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            :aria-describedby="form.errors.name ? 'name-error' : 'name-count'"
                            :aria-invalid="Boolean(form.errors.name)"
                            :maxlength="NAME_MAX_LENGTH"
                            autocomplete="off"
                            placeholder="Product team"
                            required
                            type="text"
                        />
                        <InputError id="name-error" :message="form.errors.name" />
                        <p v-if="!form.errors.name" id="name-count" class="text-xs text-muted-foreground tabular-nums">
                            {{ form.name.length }}/{{ NAME_MAX_LENGTH }} characters
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            :aria-describedby="form.errors.description ? 'description-error' : 'description-count'"
                            :aria-invalid="Boolean(form.errors.description)"
                            :maxlength="DESCRIPTION_MAX_LENGTH"
                            class="min-h-24 resize-y"
                            placeholder="What is this workspace for?"
                            rows="3"
                        />
                        <InputError id="description-error" :message="form.errors.description" />
                        <p
                            v-if="!form.errors.description"
                            id="description-count"
                            class="text-xs text-muted-foreground tabular-nums"
                        >
                            {{ form.description.length }}/{{ DESCRIPTION_MAX_LENGTH }} characters
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <div class="flex items-center gap-2">
                            <Button
                                v-if="form.isDirty"
                                :disabled="form.processing"
                                class="cursor-pointer"
                                type="button"
                                variant="ghost"
                                @click="discardChanges"
                            >
                                Discard
                            </Button>
                            <Button
                                :disabled="form.processing || !form.isDirty"
                                class="min-w-32 cursor-pointer"
                                type="submit"
                            >
                                <Loader2 v-if="form.processing" class="animate-spin" aria-hidden="true" />
                                {{ form.processing ? 'Saving…' : form.recentlySuccessful ? 'Saved' : 'Save changes' }}
                            </Button>
                        </div>
                    </div>
                </form>
            </section>

            <section aria-labelledby="delete-heading" class="mt-12">
                <h2 id="delete-heading" class="mb-4 border-b pb-3 text-xl font-semibold tracking-tight sm:text-2xl">
                    Delete workspace
                </h2>

                <div
                    class="flex flex-col gap-4 rounded-2xl border border-destructive/30 bg-destructive/5 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5"
                >
                    <p class="max-w-prose text-sm text-muted-foreground">
                        Permanently delete <span class="font-medium text-foreground">{{ workspace.name }}</span
                        >. {{ deleteConsequences }} This can’t be undone.
                    </p>
                    <Button
                        class="shrink-0 cursor-pointer self-start sm:self-auto"
                        variant="destructive"
                        @click="showDeleteDialog = true"
                    >
                        <Trash2 aria-hidden="true" />
                        Delete workspace
                    </Button>
                </div>
            </section>

            <p aria-live="polite" class="sr-only">{{ announcement }}</p>
        </div>

        <Dialog v-model:open="showDeleteDialog">
            <DialogContent class="sm:max-w-md">
                <form class="grid gap-5" @submit.prevent="deleteWorkspace">
                    <DialogHeader>
                        <span
                            aria-hidden="true"
                            class="mx-auto mb-1 flex size-10 items-center justify-center rounded-full bg-destructive/10 text-destructive sm:mx-0"
                        >
                            <TriangleAlert class="size-5" />
                        </span>
                        <DialogTitle class="break-words">Delete {{ workspace.name }}?</DialogTitle>
                        <DialogDescription>{{ deleteConsequences }} This can’t be undone.</DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label class="block leading-normal font-normal" for="delete-confirmation">
                            Type <span class="font-semibold break-all">{{ workspace.name }}</span> to confirm
                            <span aria-hidden="true" class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="delete-confirmation"
                            v-model="deleteConfirmation"
                            :disabled="isDeleting"
                            autocapitalize="off"
                            autocomplete="off"
                            required
                            spellcheck="false"
                        />
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button :disabled="isDeleting" class="cursor-pointer" type="button" variant="outline">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            :disabled="!isDeleteConfirmed || isDeleting"
                            class="cursor-pointer"
                            type="submit"
                            variant="destructive"
                        >
                            <Loader2 v-if="isDeleting" class="animate-spin" aria-hidden="true" />
                            {{ isDeleting ? 'Deleting…' : 'Delete workspace' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
