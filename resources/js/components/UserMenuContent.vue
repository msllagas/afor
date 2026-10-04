<script setup lang="ts">
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { type Appearance, useAppearance } from '@/composables/useAppearance';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Monitor, Moon, Settings, Sun } from 'lucide-vue-next';
import { DropdownMenuRadioGroup, DropdownMenuRadioItem } from 'reka-ui';

interface Props {
    user: User;
}

const { appearance, updateAppearance } = useAppearance();

const themes = [
    { value: 'light', label: 'Light', Icon: Sun },
    { value: 'dark', label: 'Dark', Icon: Moon },
    { value: 'system', label: 'System', Icon: Monitor },
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
        <DropdownMenuLabel id="theme-label" class="pb-1 text-xs font-medium text-muted-foreground">
            Theme
        </DropdownMenuLabel>
        <DropdownMenuRadioGroup
            :model-value="appearance"
            aria-labelledby="theme-label"
            class="mx-1 mb-1 grid grid-cols-3 gap-1 rounded-lg bg-muted p-1"
            @update:model-value="updateAppearance($event as Appearance)"
        >
            <!-- Keep the menu open so the new theme is visible right away. -->
            <DropdownMenuRadioItem
                v-for="{ value, label, Icon } in themes"
                :key="value"
                :value="value"
                class="flex cursor-pointer flex-col items-center gap-1 rounded-md px-1 py-1.5 text-xs font-medium text-muted-foreground transition-colors outline-none select-none focus-visible:ring-[3px] focus-visible:ring-ring/50 data-[highlighted]:text-foreground data-[state=checked]:bg-background data-[state=checked]:text-foreground data-[state=checked]:shadow-xs"
                @select.prevent
            >
                <component :is="Icon" class="size-4" aria-hidden="true" />
                {{ label }}
            </DropdownMenuRadioItem>
        </DropdownMenuRadioGroup>
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
