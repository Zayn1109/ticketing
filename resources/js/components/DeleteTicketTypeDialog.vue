<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import TicketTypeController from '@/actions/App/Http/Controllers/Organizer/TicketTypeController';
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
    session: { id: number };
    ticketType: { id: number; name: string };
}>();
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <button
                type="button"
                class="text-sm text-destructive hover:underline"
            >
                刪除
            </button>
        </DialogTrigger>
        <DialogContent>
            <Form
                v-bind="
                    TicketTypeController.destroy.form({
                        organizer: props.organizer,
                        event: props.event,
                        session: props.session,
                        ticketType: props.ticketType,
                    })
                "
                :options="{ preserveScroll: true }"
                class="space-y-6"
                v-slot="{ processing }"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle>
                        確定要刪除「{{ props.ticketType.name }}」嗎？
                    </DialogTitle>
                    <DialogDescription>
                        票種將被永久刪除，無法復原。
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
                        刪除票種
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
