<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import TicketTypeController from '@/actions/App/Http/Controllers/Organizer/TicketTypeController';
import TicketTypeFormFields from '@/components/TicketTypeFormFields.vue';
import { Button } from '@/components/ui/button';
import { show } from '@/routes/organizers/events';

const props = defineProps<{
    organizer: { id: number; name: string };
    event: { id: number; name: string };
    session: {
        id: number;
        name: string | null;
        venue_name: string;
        starts_at: string;
        ends_at: string;
        timezone: string;
        capacity: number | null;
    };
}>();

const formatDateTime = (iso: string) =>
    new Intl.DateTimeFormat('zh-TW', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: props.session.timezone,
    }).format(new Date(iso));
</script>

<template>
    <Head :title="`新增票種 - ${props.event.name}`" />

    <div class="flex flex-col gap-6 p-4">
        <header class="flex flex-col gap-2">
            <Link
                :href="show({ organizer: props.organizer, event: props.event })"
                class="text-sm text-muted-foreground hover:underline"
            >
                ← {{ props.event.name }}
            </Link>
            <h1 class="text-2xl font-semibold">新增票種</h1>
            <p class="text-sm text-muted-foreground">
                {{ props.session.name ?? props.session.venue_name }} ·
                {{ formatDateTime(props.session.starts_at) }} –
                {{ formatDateTime(props.session.ends_at) }}
                （{{ props.session.timezone }}）
            </p>
        </header>

        <Form
            v-bind="
                TicketTypeController.store.form({
                    organizer: props.organizer,
                    event: props.event,
                    session: props.session,
                })
            "
            class="flex max-w-2xl flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <TicketTypeFormFields
                :errors="errors"
                :timezone="props.session.timezone"
                :capacity="props.session.capacity"
            />

            <div>
                <Button :disabled="processing">新增</Button>
            </div>
        </Form>
    </div>
</template>
