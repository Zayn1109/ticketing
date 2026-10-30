<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import EventController from '@/actions/App/Http/Controllers/Organizer/EventController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/organizers/events';

const props = defineProps<{
    organizer: { id: number; name: string };
}>();
</script>

<template>
    <Head :title="`建立活動 - ${props.organizer.name}`" />

    <div class="flex flex-col gap-6 p-4">
        <header class="flex flex-col gap-2">
            <Link
                :href="index(props.organizer)"
                class="text-sm text-muted-foreground hover:underline"
            >
                ← {{ props.organizer.name }} 的活動
            </Link>
            <h1 class="text-2xl font-semibold">建立活動</h1>
        </header>

        <Form
            v-bind="EventController.store.form(props.organizer)"
            class="flex max-w-xl flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">名稱</Label>
                <Input id="name" name="name" required maxlength="255" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="description">說明（選填）</Label>
                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    maxlength="5000"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
                />
                <InputError :message="errors.description" />
            </div>

            <p class="text-sm text-muted-foreground">
                活動建立後為草稿，之後再新增場次與票種並發布。
            </p>

            <div>
                <Button :disabled="processing">建立</Button>
            </div>
        </Form>
    </div>
</template>
