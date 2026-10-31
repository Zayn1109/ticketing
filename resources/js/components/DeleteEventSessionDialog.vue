<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import EventSessionController from '@/actions/App/Http/Controllers/Organizer/EventSessionController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const props = defineProps<{
    organizer: { id: number };
    event: { id: number };
    session: { id: number; name: string | null; venue_name: string };
    ticketTypeCount: number;
}>();
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <Button variant="outline" size="sm" class="text-destructive">
                刪除
            </Button>
        </DialogTrigger>
        <DialogContent>
            <Form
                v-bind="
                    EventSessionController.destroy.form({
                        organizer: props.organizer,
                        event: props.event,
                        session: props.session,
                    })
                "
                :options="{ preserveScroll: true }"
                class="space-y-6"
                v-slot="{ processing }"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle>
                        確定要刪除「{{
                            props.session.name ?? props.session.venue_name
                        }}」嗎？
                    </DialogTitle>
                    <DialogDescription>
                        場次
                        <template v-if="props.ticketTypeCount > 0">
                            與其 {{ props.ticketTypeCount }} 個票種
                        </template>
                        將被永久刪除，無法復原。
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">取消</Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing"
                    >
                        刪除場次
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
