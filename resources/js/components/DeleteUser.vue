<script setup lang="ts">
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { Form, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// Components
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { OwnedWorkspaceSummary } from '@/types';

const props = defineProps<{
    /** The workspaces deleted with the account. */
    ownedWorkspaces: OwnedWorkspaceSummary[];
}>();

const page = usePage();
const sharedWorkspaceCount = computed(() => page.props.sharedWorkspaces.length);

const plural = (count: number, word: string) => `${count} ${word}${count === 1 ? '' : 's'}`;
const memberImpact = (workspace: OwnedWorkspaceSummary) =>
    workspace.members_count ? `${plural(workspace.members_count, 'member')} will lose access` : 'Only you';

const passwordInput = ref<InstanceType<typeof Input> | null>(null);
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall title="Delete account" description="Delete your account and the workspaces you own" />
        <div class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Warning</p>
                <p class="text-sm">Please proceed with caution, this cannot be undone.</p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive" data-test="delete-user-button">Delete account</Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.$el?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Are you sure you want to delete your account?</DialogTitle>
                            <DialogDescription>
                                Your account is permanently deleted and can't be recovered. Enter your password to
                                confirm.
                            </DialogDescription>
                        </DialogHeader>

                        <div v-if="props.ownedWorkspaces.length" class="space-y-2 text-sm">
                            <p>
                                {{
                                    props.ownedWorkspaces.length === 1
                                        ? 'The workspace you own will be deleted too, with all of its boards and cards:'
                                        : 'The workspaces you own will be deleted too, with all of their boards and cards:'
                                }}
                            </p>
                            <ul class="max-h-48 divide-y overflow-y-auto rounded-lg border">
                                <li
                                    v-for="workspace in props.ownedWorkspaces"
                                    :key="workspace.id"
                                    class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5 px-3 py-2"
                                >
                                    <span class="min-w-0 font-medium break-words">{{ workspace.name }}</span>
                                    <span
                                        :class="workspace.members_count ? 'text-destructive' : 'text-muted-foreground'"
                                        class="shrink-0 text-xs"
                                    >
                                        {{ memberImpact(workspace) }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                        <p v-if="sharedWorkspaceCount" class="text-sm text-muted-foreground">
                            You'll also leave {{ plural(sharedWorkspaceCount, 'workspace') }} other people own. Their
                            boards stay with them.
                        </p>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                ref="passwordInput"
                                placeholder="Password"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    Cancel
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                Delete account
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
