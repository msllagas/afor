<script setup lang="ts">
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { useAppearance } from '@/composables/useAppearance';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Monitor, Moon, Settings, Sun } from 'lucide-vue-next';

interface Props {
    user: User;
}

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun },
    { value: 'dark', Icon: Moon },
    { value: 'system', Icon: Monitor },
] as const;

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <div class="flex w-full items-center gap-0.5 px-2 py-1">
            <button
                v-for="{ value, Icon } in tabs"
                :key="value"
                @click="updateAppearance(value)"
                :class="[
                    'flex h-8 flex-1 items-center justify-center rounded-md transition-colors',
                    appearance === value
                        ? 'bg-neutral-700 text-neutral-100'
                        : 'text-neutral-400 hover:bg-neutral-700/60 hover:text-neutral-200',
                ]"
            >
                <component :is="Icon" class="h-4 w-4" />
            </button>
        </div>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full" :href="edit()" prefetch as="button">
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link class="block w-full" :href="logout()" @click="handleLogout" as="button" data-test="logout-button">
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
