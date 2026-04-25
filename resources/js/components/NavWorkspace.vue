<script lang="ts" setup>
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

defineProps<{
    items: {
        title: string;
        logo?: string;
        url: string;
        isActive?: boolean;
        items?: {
            title: string;
            url: string;
        }[];
    }[];
    title: string;
}>();

const page = usePage();
</script>
<template>
    <SidebarGroup>
        <SidebarGroupLabel>{{ title }}</SidebarGroupLabel>
        <SidebarMenu>
            <Collapsible
                v-for="item in items"
                :key="item.title"
                :default-open="item.isActive"
                as-child
                class="group/collapsible"
            >
                <SidebarMenuItem>
                    <CollapsibleTrigger as-child>
                        <SidebarMenuButton :tooltip="item.title">
                            <img
                                v-if="item.logo"
                                :alt="item.title"
                                :src="item.logo"
                                class="h-5 w-5 rounded-md object-cover ring-1 ring-border/50"
                            />
                            <div
                                v-else
                                class="flex h-5 w-5 items-center justify-center rounded-md bg-linear-to-br from-primary/20 to-accent/20 text-[10px] font-bold text-primary ring-1 ring-border/50"
                            >
                                {{ item.title.charAt(0).toUpperCase() }}
                            </div>
                            <span>{{ item.title }}</span>
                            <ChevronRight
                                class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                            />
                        </SidebarMenuButton>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
                                <SidebarMenuSubButton :is-active="urlIsActive(subItem.url, page.url)" as-child>
                                    <Link :href="subItem.url">
                                        <span>{{ subItem.title }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </SidebarMenuItem>
            </Collapsible>
        </SidebarMenu>
    </SidebarGroup>
</template>
