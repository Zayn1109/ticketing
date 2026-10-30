<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import EventSessionController from '@/actions/App/Http/Controllers/Organizer/EventSessionController';
import EventSessionFormFields from '@/components/EventSessionFormFields.vue';
import { Button } from '@/components/ui/button';
import { show } from '@/routes/organizers/events';

const props = defineProps<{
    organizer: { id: number; name: string };
    event: { id: number; name: string };
}>();
</script>

<template>
    <Head :title="`新增場次 - ${props.event.name}`" />

    <div class="flex flex-col gap-6 p-4">
        <header class="flex flex-col gap-2">
            <Link
                :href="show({ organizer: props.organizer, event: props.event })"
                class="text-sm text-muted-foreground hover:underline"
            >
                ← {{ props.event.name }}
            </Link>
            <h1 class="text-2xl font-semibold">新增場次</h1>
        </header>

        <Form
            v-bind="
                EventSessionController.store.form({
                    organizer: props.organizer,
                    event: props.event,
                })
            "
            class="flex max-w-2xl flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <EventSessionFormFields :errors="errors" />

            <div>
                <Button :disabled="processing">新增</Button>
            </div>
        </Form>
    </div>
</template>
