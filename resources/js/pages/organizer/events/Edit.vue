<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import EventController from '@/actions/App/Http/Controllers/Organizer/EventController';
import EventFormFields from '@/components/EventFormFields.vue';
import { Button } from '@/components/ui/button';
import { show } from '@/routes/organizers/events';

const props = defineProps<{
    organizer: { id: number; name: string };
    event: { id: number; name: string; description: string | null };
}>();
</script>

<template>
    <Head :title="`編輯活動 - ${props.event.name}`" />

    <div class="flex flex-col gap-6 p-4">
        <header class="flex flex-col gap-2">
            <Link
                :href="show({ organizer: props.organizer, event: props.event })"
                class="text-sm text-muted-foreground hover:underline"
            >
                ← {{ props.event.name }}
            </Link>
            <h1 class="text-2xl font-semibold">編輯活動</h1>
        </header>

        <Form
            v-bind="
                EventController.update.form({
                    organizer: props.organizer,
                    event: props.event,
                })
            "
            class="flex max-w-xl flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <EventFormFields :errors="errors" :event="props.event" />

            <div>
                <Button :disabled="processing">儲存</Button>
            </div>
        </Form>
    </div>
</template>
