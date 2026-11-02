<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import TicketTypeController from '@/actions/App/Http/Controllers/Organizer/TicketTypeController';
import TicketTypeFormFields from '@/components/TicketTypeFormFields.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/dateTime';
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
    // 販售時間為場次時區的當地時間，格式 YYYY-MM-DDTHH:mm（datetime-local）
    ticketType: {
        id: number;
        name: string;
        price: number;
        quota: number;
        sale_start_at: string;
        sale_end_at: string;
        max_per_user: number | null;
    };
}>();
</script>

<template>
    <Head :title="`編輯票種 - ${props.event.name}`" />

    <div class="flex flex-col gap-6 p-4">
        <header class="flex flex-col gap-2">
            <Link
                :href="show({ organizer: props.organizer, event: props.event })"
                class="text-sm text-muted-foreground hover:underline"
            >
                ← {{ props.event.name }}
            </Link>
            <h1 class="text-2xl font-semibold">編輯票種</h1>
            <p class="text-sm text-muted-foreground">
                {{ props.session.name ?? props.session.venue_name }} ·
                {{
                    formatDateTime(
                        props.session.starts_at,
                        props.session.timezone,
                    )
                }}
                –
                {{
                    formatDateTime(
                        props.session.ends_at,
                        props.session.timezone,
                    )
                }}
                （{{ props.session.timezone }}）
            </p>
        </header>

        <Form
            v-bind="
                TicketTypeController.update.form({
                    organizer: props.organizer,
                    event: props.event,
                    session: props.session,
                    ticketType: props.ticketType,
                })
            "
            class="flex max-w-2xl flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <TicketTypeFormFields
                :errors="errors"
                :timezone="props.session.timezone"
                :capacity="props.session.capacity"
                :ticket-type="props.ticketType"
            />

            <div>
                <Button :disabled="processing">儲存</Button>
            </div>
        </Form>
    </div>
</template>
