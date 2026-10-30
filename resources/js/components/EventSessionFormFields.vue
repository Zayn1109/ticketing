<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Field =
    | 'name'
    | 'venue_name'
    | 'venue_address'
    | 'timezone'
    | 'starts_at'
    | 'ends_at'
    | 'doors_open_at'
    | 'capacity';

const props = defineProps<{
    errors: Partial<Record<Field, string>>;
    // 時間為場次時區的當地時間，格式 YYYY-MM-DDTHH:mm（datetime-local）
    session?: {
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

const selectedTimezone = props.session?.timezone ?? 'Asia/Taipei';

// 瀏覽器清單不一定包含已存的時區（例如舊名稱），缺的話補進去
const timezones = Intl.supportedValuesOf('timeZone');
if (!timezones.includes(selectedTimezone)) {
    timezones.unshift(selectedTimezone);
}

const fieldClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="grid gap-2">
            <Label for="name">場次名稱（選填）</Label>
            <Input
                id="name"
                name="name"
                :default-value="props.session?.name ?? ''"
                maxlength="255"
                placeholder="例如：台北場"
            />
            <InputError :message="props.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="venue_name">場地名稱</Label>
            <Input
                id="venue_name"
                name="venue_name"
                :default-value="props.session?.venue_name"
                required
                maxlength="255"
            />
            <InputError :message="props.errors.venue_name" />
        </div>

        <div class="grid gap-2">
            <Label for="venue_address">場地地址（選填）</Label>
            <Input
                id="venue_address"
                name="venue_address"
                :default-value="props.session?.venue_address ?? ''"
                maxlength="255"
            />
            <InputError :message="props.errors.venue_address" />
        </div>

        <div class="grid gap-2">
            <Label for="timezone">時區</Label>
            <select id="timezone" name="timezone" :class="fieldClass" required>
                <option
                    v-for="timezone in timezones"
                    :key="timezone"
                    :value="timezone"
                    :selected="timezone === selectedTimezone"
                >
                    {{ timezone }}
                </option>
            </select>
            <p class="text-sm text-muted-foreground">
                下方時間皆以此時區的當地時間填寫
            </p>
            <InputError :message="props.errors.timezone" />
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="doors_open_at">入場時間（選填）</Label>
                <input
                    id="doors_open_at"
                    name="doors_open_at"
                    type="datetime-local"
                    :value="props.session?.doors_open_at ?? ''"
                    :class="fieldClass"
                />
                <InputError :message="props.errors.doors_open_at" />
            </div>

            <div class="grid gap-2">
                <Label for="starts_at">開始時間</Label>
                <input
                    id="starts_at"
                    name="starts_at"
                    type="datetime-local"
                    :value="props.session?.starts_at ?? ''"
                    :class="fieldClass"
                    required
                />
                <InputError :message="props.errors.starts_at" />
            </div>

            <div class="grid gap-2">
                <Label for="ends_at">結束時間</Label>
                <input
                    id="ends_at"
                    name="ends_at"
                    type="datetime-local"
                    :value="props.session?.ends_at ?? ''"
                    :class="fieldClass"
                    required
                />
                <InputError :message="props.errors.ends_at" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="capacity">容量（選填）</Label>
            <Input
                id="capacity"
                name="capacity"
                type="number"
                min="1"
                :default-value="props.session?.capacity ?? ''"
            />
            <InputError :message="props.errors.capacity" />
        </div>
    </div>
</template>
