<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import workspaceInvitationsRoutes from '@/routes/workspace-invitations';
import type { Invitation } from '@/types';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    invitation: Invitation;
    savedToken?: boolean;
}>();

function acceptInvitation() {
    router.post(
        workspaceInvitationsRoutes.accept({
            workspace: props.invitation.workspace.id,
            token: props.invitation.token,
        }),
    );
}

function handleLogin() {
    console.log('handle login here');
}
function handleRegister() {
    console.log('handle register here');
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 dark:bg-gray-900">
        <div
            class="w-full max-w-2xl space-y-8 rounded-2xl border border-gray-200 bg-white p-10 text-center shadow-xl dark:border-gray-800 dark:bg-gray-950"
        >
            <div class="space-y-3">
                <p class="text-sm tracking-widest text-gray-500 uppercase dark:text-gray-400">Workspace Invitation</p>

                <div
                    class="text-lg leading-relaxed font-medium text-gray-700 md:text-xl lg:text-2xl dark:text-gray-300"
                >
                    <span class="font-semibold text-gray-900 dark:text-white">
                        {{ invitation.inviter.name }}
                    </span>
                    <span class="mx-1">invited you to</span>
                    <span class="font-bold text-primary">
                        {{ invitation.workspace.name }}
                    </span>
                </div>
            </div>

            <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
                <Button
                    v-if="$page.props.auth.user"
                    class="w-full px-10 font-semibold sm:w-auto"
                    size="lg"
                    @click="acceptInvitation"
                >
                    Accept Invitation
                </Button>

                <template v-else>
                    <Button
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                        size="lg"
                        variant="outline"
                        @click="handleLogin"
                    >
                        Log in
                    </Button>

                    <Button
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                        size="lg"
                        @click="handleRegister"
                    >
                        Create Account
                    </Button>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
