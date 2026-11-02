<script setup lang="ts">
import DateTimePicker from '@/components/DateTimePicker.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Field =
    | 'name'
    | 'price'
    | 'quota'
    | 'sale_start_at'
    | 'sale_end_at'
    | 'max_per_user';

const props = defineProps<{
    errors: Partial<Record<Field, string>>;
    // 所屬場次的時區與容量，用來提示時間的解讀方式與配額上限
    timezone: string;
    capacity: number | null;
    // 時間為場次時區的當地時間，格式 YYYY-MM-DDTHH:mm（datetime-local）
    ticketType?: {
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
    <div class="flex flex-col gap-6">
        <div class="grid gap-2">
            <Label for="name">票種名稱</Label>
            <Input
                id="name"
                name="name"
                :default-value="props.ticketType?.name"
                required
                maxlength="255"
                placeholder="例如：早鳥票"
            />
            <InputError :message="props.errors.name" />
        </div>

        <div class="grid items-start gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="price">價格（新台幣元）</Label>
                <Input
                    id="price"
                    name="price"
                    type="number"
                    min="0"
                    max="1000000"
                    required
                    :default-value="props.ticketType?.price"
                />
                <p class="text-sm text-muted-foreground">填 0 為免費票</p>
                <InputError :message="props.errors.price" />
            </div>

            <div class="grid gap-2">
                <Label for="quota">配額</Label>
                <Input
                    id="quota"
                    name="quota"
                    type="number"
                    min="1"
                    :max="props.capacity ?? undefined"
                    required
                    :default-value="props.ticketType?.quota"
                />
                <p
                    v-if="props.capacity !== null"
                    class="text-sm text-muted-foreground"
                >
                    不可超過場次容量 {{ props.capacity }}
                </p>
                <InputError :message="props.errors.quota" />
            </div>

            <div class="grid gap-2">
                <Label for="max_per_user">每人限購（選填）</Label>
                <Input
                    id="max_per_user"
                    name="max_per_user"
                    type="number"
                    min="1"
                    :default-value="props.ticketType?.max_per_user ?? ''"
                />
                <p class="text-sm text-muted-foreground">空白表示不限</p>
                <InputError :message="props.errors.max_per_user" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="sale_start_at">開賣時間</Label>
                <DateTimePicker
                    id="sale_start_at"
                    name="sale_start_at"
                    :default-value="props.ticketType?.sale_start_at"
                />
                <InputError :message="props.errors.sale_start_at" />
            </div>

            <div class="grid gap-2">
                <Label for="sale_end_at">結束販售時間</Label>
                <DateTimePicker
                    id="sale_end_at"
                    name="sale_end_at"
                    :default-value="props.ticketType?.sale_end_at"
                />
                <InputError :message="props.errors.sale_end_at" />
            </div>
        </div>
        <p class="-mt-4 text-sm text-muted-foreground">
            販售時間以場次時區（{{
                props.timezone
            }}）的當地時間填寫，最晚可到場次結束
        </p>
    </div>
</template>
