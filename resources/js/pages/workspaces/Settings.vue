<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import workspaceRoutes from '@/routes/workspaces';
import type { BreadcrumbItem, Workspace } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Camera, Loader2, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    workspace: Workspace;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Workspace', href: workspaceRoutes.home(props.workspace.id).url },
    { title: 'Settings', href: workspaceRoutes.settings(props.workspace.id).url },
];

const fileInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(props.workspace.logo ?? null);
const isDragging = ref(false);

const form = useForm({
    name: props.workspace.name,
    description: props.workspace.description ?? '',
    logo: null as File | null,
    _method: 'patch',
});

function triggerFileInput() {
    fileInput.value?.click();
}

function handleFile(file: File) {
    if (file.size > 2 * 1024 * 1024) {
        return;
    }

    if (!file.type.startsWith('image/')) return;

    if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
        return;
    }

    form.logo = file;
    previewUrl.value = URL.createObjectURL(file);
}

function onFileChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) handleFile(file);
}

function onDrop(e: DragEvent) {
    isDragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file) handleFile(file);
}

function removePhoto() {
    form.logo = null;
    previewUrl.value = null;
    if (fileInput.value) fileInput.value.value = '';
}

function submit() {
    form.post(workspaceRoutes.update(props.workspace.id).url, {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        only: ['workspace'],
    });
}
</script>

<template>
    <Head :title="workspace.name + ' - Settings'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-10 py-12">
            <div class="max-w-2xl space-y-8">
                <!-- Header -->
                <div class="space-y-1">
                    <h1 class="text-xl font-bold tracking-tight">Workspace Settings</h1>
                    <p class="text-sm text-muted-foreground">Manage your workspace's identity and details.</p>
                </div>

                <hr class="border-border/50" />

                <form class="space-y-8" @submit.prevent="submit">
                    <!-- Photo -->
                    <div class="space-y-3">
                        <Label class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase">
                            Workspace Photo
                        </Label>

                        <div class="flex items-center gap-5">
                            <!-- Avatar preview -->
                            <div
                                :class="
                                    isDragging
                                        ? 'scale-105 border-primary shadow-lg shadow-primary/20'
                                        : 'border-border/60 hover:border-primary/40'
                                "
                                class="group relative h-20 w-20 shrink-0 cursor-pointer overflow-hidden rounded-2xl border-2 transition-all"
                                @click="triggerFileInput"
                                @dragleave="isDragging = false"
                                @dragover.prevent="isDragging = true"
                                @drop.prevent="onDrop"
                            >
                                <img
                                    v-if="previewUrl"
                                    :src="previewUrl"
                                    alt="Workspace photo"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-linear-to-br from-primary/10 to-accent/10"
                                >
                                    <span class="text-2xl font-bold text-primary/60">
                                        {{ workspace.name.charAt(0) }}
                                    </span>
                                </div>

                                <!-- Overlay on hover -->
                                <div
                                    class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 backdrop-blur-[1px] transition-opacity group-hover:opacity-100"
                                >
                                    <Camera class="h-5 w-5 text-white" />
                                </div>
                            </div>

                            <!-- Upload actions -->
                            <div class="space-y-2">
                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        class="h-8 gap-1.5 rounded-lg border-border/60 bg-muted/60 text-xs font-medium shadow-sm"
                                        size="sm"
                                        type="button"
                                        variant="outline"
                                        @click="triggerFileInput"
                                    >
                                        <Camera class="h-3.5 w-3.5" />
                                        {{ previewUrl ? 'Change photo' : 'Upload photo' }}
                                    </Button>

                                    <Button
                                        v-if="previewUrl"
                                        class="h-8 gap-1.5 rounded-lg border-border/60 bg-muted/60 text-xs font-medium text-muted-foreground shadow-sm hover:border-destructive/30 hover:bg-destructive/10 hover:text-destructive"
                                        size="sm"
                                        type="button"
                                        variant="outline"
                                        @click="removePhoto"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                        Remove
                                    </Button>
                                </div>
                                <p class="text-[11px] text-muted-foreground">
                                    JPG, PNG or GIF · Max 2MB · Recommended 256×256px
                                </p>
                            </div>
                        </div>

                        <input ref="fileInput" accept="image/*" class="hidden" type="file" @change="onFileChange" />
                    </div>

                    <hr class="border-border/50" />

                    <!-- Name -->
                    <div class="grid gap-2">
                        <Label for="name"> Workspace Name </Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            :class="{ 'border-destructive': form.errors.name }"
                            maxlength="64"
                            placeholder="My Afor Workspace"
                            type="text"
                        />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        <p v-else class="text-xs text-muted-foreground">{{ form.name.length }}/64 characters</p>
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2">
                        <Label for="description">
                            Description
                            <span class="ml-1 font-normal tracking-normal text-muted-foreground/60 normal-case"
                                >· optional</span
                            >
                        </Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            :class="{ 'border-destructive': form.errors.description }"
                            class="resize-none dark:bg-muted/30"
                            maxlength="280"
                            placeholder="What is this workspace for?"
                            rows="3"
                        />
                        <p v-if="form.errors.description" class="text-xs text-destructive">
                            {{ form.errors.description }}
                        </p>
                        <p v-else class="text-[11px] text-muted-foreground">
                            {{ form.description.length }}/280 characters
                        </p>
                    </div>

                    <!-- Save -->
                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing || !form.isDirty" size="sm" type="submit">
                            <Loader2 v-if="form.processing" class="h-3.5 w-3.5 animate-spin" />
                            {{ form.processing ? 'Saving…' : 'Save changes' }}
                        </Button>
                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </form>

                <!-- Danger Zone -->
                <div class="space-y-3 rounded-xl border border-destructive/20 bg-destructive/3 p-5">
                    <div class="space-y-0.5">
                        <h3 class="text-sm font-semibold text-destructive">Danger Zone</h3>
                        <p class="text-xs text-muted-foreground">
                            Deleting a workspace is permanent and cannot be undone. All boards and data will be lost.
                        </p>
                    </div>
                    <Button
                        class="h-8 gap-1.5 rounded-lg border-destructive/30 bg-destructive/5 text-xs font-medium text-destructive shadow-sm hover:bg-destructive hover:text-white"
                        size="sm"
                        type="button"
                        variant="outline"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        Delete workspace
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
