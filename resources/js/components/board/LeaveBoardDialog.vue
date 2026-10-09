<script lang="ts" setup>
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
import boardRoutes from '@/routes/boards';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    boardId: string;
    boardName: string;
    workspaceName: string;
}>();

const emit = defineEmits<{
    /** The leave came back 404: the user may already be off the board. The board page works out what happened. */
    notFound: [failureMessage: string];
}>();

const open = defineModel<boolean>('open', { default: false });

const isLeaving = ref(false);

function handleFailure(status?: number) {
    const failureMessage = 'Couldn’t leave the board';

    if (status === 404) {
        emit('notFound', failureMessage);

        return;
    }

    const descriptions: Record<number, string> = {
        403: "You own this workspace, so you're on every board in it.",
        419: 'Your session expired. Refresh the page, then try again.',
    };
    const canRetry = status === undefined || !(status in descriptions);

    toast.error(failureMessage, {
        description:
            status === undefined
                ? 'Check your connection, then try again.'
                : (descriptions[status] ?? 'Something went wrong on our end. Try again in a moment.'),
        action: canRetry ? { label: 'Try again', onClick: leaveBoard } : undefined,
    });
}

function leaveBoard() {
    if (isLeaving.value) {
        return;
    }

    const boardName = props.boardName;

    router.delete(boardRoutes.leave(props.boardId).url, {
        replace: true,
        onStart: () => (isLeaving.value = true),
        onSuccess: () =>
            toast.success(`You left “${boardName}”`, {
                description: 'The workspace owner can add you back.',
            }),
        onHttpException: (response) => {
            handleFailure(response.status);

            return false;
        },
        onNetworkError: () => {
            handleFailure();

            return false;
        },
        onFinish: () => {
            isLeaving.value = false;
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="break-words">Leave {{ boardName }}?</DialogTitle>
                <DialogDescription>
                    You stay in {{ workspaceName }}, but only the workspace owner can add you back.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button :disabled="isLeaving" class="cursor-pointer" type="button" variant="outline">
                        Cancel
                    </Button>
                </DialogClose>
                <Button :disabled="isLeaving" class="cursor-pointer" variant="destructive" @click="leaveBoard">
                    {{ isLeaving ? 'Leaving…' : 'Leave board' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
