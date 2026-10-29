<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Building2 } from '@lucide/vue';
import { computed } from 'vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { index } from '@/routes/organizers/events';
import type { OrganizerRole } from '@/types';

const page = usePage();
const organizers = computed(() => page.props.organizers);

const { isCurrentOrParentUrl } = useCurrentUrl();

const roleLabels: Record<OrganizerRole, string> = {
    owner: '擁有者',
    admin: '管理員',
    scanner: '驗票員',
};
</script>

<template>
    <SidebarGroup v-if="organizers.length > 0" class="px-2 py-0">
        <SidebarGroupLabel>主辦方</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem
                v-for="organizer in organizers"
                :key="organizer.id"
            >
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentOrParentUrl(index(organizer))"
                    :tooltip="organizer.name"
                >
                    <Link :href="index(organizer)">
                        <Building2 />
                        <span>{{ organizer.name }}</span>
                        <span class="ml-auto text-xs text-muted-foreground">
                            {{ roleLabels[organizer.role] }}
                        </span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
