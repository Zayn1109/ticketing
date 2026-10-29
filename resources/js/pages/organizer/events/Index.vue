<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { eventStatusLabels, eventStatusVariants } from '@/lib/eventStatus';
import type { EventStatus } from '@/lib/eventStatus';
import { show } from '@/routes/organizers/events';

const props = defineProps<{
    organizer: { id: number; name: string };
    events: {
        id: number;
        name: string;
        status: EventStatus;
        sessions_count: number;
    }[];
}>();
</script>

<template>
    <Head :title="`${props.organizer.name} 的活動`" />

    <div class="flex flex-col gap-6 p-4">
        <header class="flex flex-col gap-1">
            <p class="text-sm text-muted-foreground">
                {{ props.organizer.name }}
            </p>
            <h1 class="text-2xl font-semibold">活動</h1>
        </header>

        <p
            v-if="props.events.length === 0"
            class="text-sm text-muted-foreground"
        >
            尚未建立活動
        </p>

        <table v-else class="w-full text-sm">
            <thead class="text-left text-muted-foreground">
                <tr>
                    <th class="py-2 font-normal">名稱</th>
                    <th class="py-2 font-normal">狀態</th>
                    <th class="py-2 font-normal">場次數</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="event in props.events"
                    :key="event.id"
                    class="border-t"
                >
                    <td class="py-2">
                        <Link
                            :href="show({ organizer: props.organizer, event })"
                            class="font-medium hover:underline"
                        >
                            {{ event.name }}
                        </Link>
                    </td>
                    <td class="py-2">
                        <Badge :variant="eventStatusVariants[event.status]">
                            {{ eventStatusLabels[event.status] }}
                        </Badge>
                    </td>
                    <td class="py-2">{{ event.sessions_count }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
