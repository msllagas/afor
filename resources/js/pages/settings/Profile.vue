<script lang="ts" setup>
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { computed, ref } from 'vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;
const { getInitials } = useInitials();
const avatarPreview = ref<string | null>(user.avatar ?? null);
const fileInputRef = ref<HTMLInputElement | null>(null);
const avatarError = ref('');
const avatarUploading = ref(false);
const savedAvatar = computed<string | undefined>(() => page.props.auth.user.avatar);

async function handleAvatarChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    avatarError.value = '';

    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
        avatarError.value = 'Image must be smaller than 2MB.';
        return;
    }

    if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
        avatarError.value = 'Please upload a valid image file.';
        return;
    }

    const dataUrl = await new Promise<string>((resolve) => {
        const reader = new FileReader();
        reader.onload = (e) => resolve(e.target?.result as string);
        reader.readAsDataURL(file);
    });

    if (fileInputRef.value) fileInputRef.value.value = '';

    avatarUploading.value = true;
    const form = useForm({
        _method: 'patch',
        avatar: file,
    });

    form.post(ProfileController.updateAvatar().url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            avatarPreview.value = dataUrl;
        },
        onError: (errors) => {
            page.props.auth.user.avatar = savedAvatar.value;
            avatarPreview.value = savedAvatar.value ?? null;
            avatarError.value = errors.avatar ?? 'Upload failed.';
        },
        onFinish: () => {
            avatarUploading.value = false;
        },
    });
}

function removeAvatar() {
    avatarPreview.value = null;
    avatarError.value = '';
    if (fileInputRef.value) fileInputRef.value.value = '';

    router.delete(ProfileController.deleteAvatar().url, {
        preserveScroll: true,
        onSuccess: () => {
            avatarPreview.value = null;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall description="Update your name and email address" title="Profile information" />

                <form>
                    <div class="grid gap-2">
                        <Label>Profile Picture</Label>
                        <div class="flex items-center gap-4">
                            <div class="group relative">
                                <div
                                    class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-2 border-border bg-muted"
                                >
                                    <img
                                        v-if="avatarPreview"
                                        :src="avatarPreview"
                                        alt="Avatar preview"
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-else class="text-2xl font-medium text-muted-foreground select-none">
                                        {{ getInitials(user.name) }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    class="absolute inset-0 flex cursor-pointer items-center justify-center rounded-full bg-black/50 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                                    @click="fileInputRef?.click()"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 text-white"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <polyline points="17 8 12 3 7 8" />
                                        <line x1="12" y1="3" x2="12" y2="15" />
                                    </svg>
                                </button>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="avatarUploading"
                                    @click="fileInputRef?.click()"
                                >
                                    <svg
                                        v-if="avatarUploading"
                                        class="mr-2 h-3 w-3 animate-spin"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                    </svg>
                                    {{
                                        avatarUploading ? 'Uploading…' : avatarPreview ? 'Change photo' : 'Upload photo'
                                    }}
                                </Button>
                                <button
                                    v-if="avatarPreview && !avatarUploading"
                                    type="button"
                                    class="text-left text-xs text-muted-foreground transition-colors hover:text-destructive"
                                    @click="removeAvatar"
                                >
                                    Remove
                                </button>
                                <p class="text-xs text-muted-foreground">JPG, PNG or GIF. Max 2MB.</p>
                            </div>

                            <input
                                ref="fileInputRef"
                                type="file"
                                name="avatar"
                                accept="image/jpeg,image/png,image/gif,image/webp"
                                class="hidden"
                                @change="handleAvatarChange"
                            />
                            <InputError :message="avatarError" />
                        </div>
                    </div>
                </form>

                <Form
                    v-slot="{ errors, processing, recentlySuccessful }"
                    class="space-y-6"
                    v-bind="ProfileController.update.form()"
                >
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            :default-value="user.name"
                            autocomplete="name"
                            class="mt-1 block w-full"
                            name="name"
                            placeholder="Full name"
                            required
                        />
                        <InputError :message="errors.name" class="mt-2" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            :default-value="user.email"
                            autocomplete="username"
                            class="mt-1 block w-full"
                            name="email"
                            placeholder="Email address"
                            required
                            type="email"
                        />
                        <InputError :message="errors.email" class="mt-2" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="processing" data-test="update-profile-button">Save</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
