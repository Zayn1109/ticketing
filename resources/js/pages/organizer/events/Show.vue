<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { eventStatusLabels, eventStatusVariants } from '@/lib/eventStatus';
import type { EventStatus } from '@/lib/eventStatus';
import { edit, index } from '@/routes/organizers/events';
import {
    create as createSession,
    edit as editSession,
} from '@/routes/organizers/events/sessions';

type TicketType = {
    id: number;
    name: string;
    price: number;
    quota: number;
    sale_start_at: string;
    sale_end_at: string;
};

type EventSession = {
    id: number;
    name: string | null;
    venue_name: string;
    venue_address: string | null;
    starts_at: string;
    ends_at: string;
    timezone: string;
    status: 'scheduled' | 'cancelled';
    can: { update: boolean };
    ticket_types: TicketType[];
};

const props = defineProps<{
    organizer: { id: number; name: string };
    can: { update: boolean; createSession: boolean };
    event: {
        id: number;
        name: string;
        description: string | null;
        status: EventStatus;
        published_at: string | null;
        sessions: EventSession[];
    };
}>();

const formatDateTime = (iso: string, timeZone: string) =>
    new Intl.DateTimeFormat('zh-TW', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone,
    }).format(new Date(iso));

const formatPrice = (price: number) =>
    new Intl.NumberFormat('zh-TW', {
        style: 'currency',
        currency: 'TWD',
        maximumFractionDigits: 0,
    }).format(price);
</script>

<template>
    <Head :title="props.event.name" />

    <div class="flex flex-col gap-6 p-4">
        <header class="flex flex-col gap-2">
            <Link
                :href="index(props.organizer)"
                class="text-sm text-muted-foreground hover:underline"
            >
                ← {{ props.organizer.name }} 的活動
            </Link>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold">{{ props.event.name }}</h1>
                <Badge :variant="eventStatusVariants[props.event.status]">
                    {{ eventStatusLabels[props.event.status] }}
                </Badge>
                <Button
                    v-if="props.can.update"
                    variant="outline"
                    class="ml-auto"
                    as-child
                >
                    <Link
                        :href="
                            edit({
                                organizer: props.organizer,
                                event: props.event,
                            })
                        "
                    >
                        編輯
                    </Link>
                </Button>
            </div>
            <p
                v-if="props.event.description"
                class="whitespace-pre-line text-muted-foreground"
            >
                {{ props.event.description }}
            </p>
        </header>

        <section class="flex flex-col gap-4">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-lg font-medium">
                    場次（{{ props.event.sessions.length }}）
                </h2>
                <Button
                    v-if="props.can.createSession"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link
                        :href="
                            createSession({
                                organizer: props.organizer,
                                event: props.event,
                            })
                        "
                    >
                        新增場次
                    </Link>
                </Button>
            </div>

            <p
                v-if="props.event.sessions.length === 0"
                class="text-sm text-muted-foreground"
            >
                尚未建立場次
            </p>

            <Card v-for="session in props.event.sessions" :key="session.id">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        {{ session.name ?? session.venue_name }}
                        <Badge
                            v-if="session.status === 'cancelled'"
                            variant="destructive"
                        >
                            已取消
                        </Badge>
                        <Button
                            v-if="session.can.update"
                            variant="outline"
                            size="sm"
                            class="ml-auto"
                            as-child
                        >
                            <Link
                                :href="
                                    editSession({
                                        organizer: props.organizer,
                                        event: props.event,
                                        session,
                                    })
                                "
                            >
                                編輯
                            </Link>
                        </Button>
                    </CardTitle>
                    <CardDescription>
                        {{
                            formatDateTime(session.starts_at, session.timezone)
                        }}
                        –
                        {{ formatDateTime(session.ends_at, session.timezone) }}
                        （{{ session.timezone }}）
                        <br />
                        {{ session.venue_name }}
                        <template v-if="session.venue_address">
                            · {{ session.venue_address }}
                        </template>
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <p
                        v-if="session.ticket_types.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        尚未建立票種
                    </p>

                    <table v-else class="w-full text-sm">
                        <thead class="text-left text-muted-foreground">
                            <tr>
                                <th class="py-2 font-normal">票種</th>
                                <th class="py-2 font-normal">價格</th>
                                <th class="py-2 font-normal">數量</th>
                                <th class="py-2 font-normal">販售期間</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="type in session.ticket_types"
                                :key="type.id"
                                class="border-t"
                            >
                                <td class="py-2">{{ type.name }}</td>
                                <td class="py-2">
                                    {{ formatPrice(type.price) }}
                                </td>
                                <td class="py-2">{{ type.quota }}</td>
                                <td class="py-2">
                                    {{
                                        formatDateTime(
                                            type.sale_start_at,
                                            session.timezone,
                                        )
                                    }}
                                    –
                                    {{
                                        formatDateTime(
                                            type.sale_end_at,
                                            session.timezone,
                                        )
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
