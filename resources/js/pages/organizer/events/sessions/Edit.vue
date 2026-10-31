<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import EventSessionController from '@/actions/App/Http/Controllers/Organizer/EventSessionController';
import EventSessionFormFields from '@/components/EventSessionFormFields.vue';
import { Button } from '@/components/ui/button';
import { show } from '@/routes/organizers/events';

const props = defineProps<{
    organizer: { id: number; name: string };
    event: { id: number; name: string };
    // 時間為場次時區的當地時間，格式 YYYY-MM-DDTHH:mm（datetime-local）
    session: {
        id: number;
        name: string | null;
        venue_name: string;
        venue_address: string | null;
        timezone: string;
        starts_at: string;
        ends_at: string;
        doors_open_at: string | null;
        capacity: number | null;
    };
}>();
</script>

<template>
    <Head :title="`編輯場次 - ${props.event.name}`" />

    <div class="flex flex-col gap-6 p-4">
        <header class="flex flex-col gap-2">
            <Link
                :href="show({ organizer: props.organizer, event: props.event })"
                class="text-sm text-muted-foreground hover:underline"
            >
                ← {{ props.event.name }}
            </Link>
            <h1 class="text-2xl font-semibold">編輯場次</h1>
        </header>

        <Form
            v-bind="
                EventSessionController.update.form({
                    organizer: props.organizer,
                    event: props.event,
                    session: props.session,
                })
            "
            class="flex max-w-2xl flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <EventSessionFormFields :errors="errors" :session="props.session" />

            <div>
                <Button :disabled="processing">儲存</Button>
            </div>
        </Form>
    </div>
</template>
