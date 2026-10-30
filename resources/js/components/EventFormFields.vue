<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    errors: Partial<Record<'name' | 'description', string>>;
    event?: { name: string; description: string | null };
}>();
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="grid gap-2">
            <Label for="name">名稱</Label>
            <Input
                id="name"
                name="name"
                :default-value="props.event?.name"
                required
                maxlength="255"
            />
            <InputError :message="props.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="description">說明（選填）</Label>
            <textarea
                id="description"
                name="description"
                :value="props.event?.description ?? ''"
                rows="6"
                maxlength="5000"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
            />
            <InputError :message="props.errors.description" />
        </div>
    </div>
</template>
