<script setup lang="ts">
import { parseDate } from '@internationalized/date';
import type { DateValue } from '@internationalized/date';
import {
    Calendar as CalendarIcon,
    ChevronLeft,
    ChevronRight,
} from '@lucide/vue';
import {
    CalendarCell,
    CalendarCellTrigger,
    CalendarGrid,
    CalendarGridBody,
    CalendarGridHead,
    CalendarGridRow,
    CalendarHeadCell,
    CalendarHeader,
    CalendarHeading,
    CalendarNext,
    CalendarPrev,
    CalendarRoot,
    PopoverContent,
    PopoverPortal,
    PopoverRoot,
    PopoverTrigger,
} from 'reka-ui';
import { computed, ref, shallowRef } from 'vue';
import { Button } from '@/components/ui/button';

const props = withDefaults(
    defineProps<{
        id: string;
        name: string;
        // 當地時間，格式 YYYY-MM-DDTHH:mm（與後端 LOCAL_DATETIME_FORMAT 一致）
        defaultValue?: string | null;
        clearable?: boolean;
    }>(),
    { defaultValue: null, clearable: false },
);

const pad = (n: number) => String(n).padStart(2, '0');

// DateValue 是帶 #private 欄位的 class，ref() 的深層 unwrap 會弄丟型別；它本身不可變，shallowRef 就夠了
const date = shallowRef<DateValue | undefined>(
    props.defaultValue ? parseDate(props.defaultValue.slice(0, 10)) : undefined,
);
const hour = ref(props.defaultValue?.slice(11, 13) ?? '19');
const minute = ref(props.defaultValue?.slice(14, 16) ?? '00');
const open = ref(false);

const hours = Array.from({ length: 24 }, (_, i) => pad(i));
// 5 分鐘一格；既有資料不在格線上（例如 19:33）時補進選項，避免編輯時被偷偷改掉
const minuteSteps = Array.from({ length: 12 }, (_, i) => pad(i * 5));
const minutes = minuteSteps.includes(minute.value)
    ? minuteSteps
    : [...minuteSteps, minute.value].sort();

// 送給後端的值；沒選日期時送空字串（後端轉成 null）
const value = computed(() =>
    date.value ? `${date.value.toString()}T${hour.value}:${minute.value}` : '',
);

// 日期本身沒有時區，用 UTC 轉 Date 只是為了取星期幾，不會位移
const weekdayFormat = new Intl.DateTimeFormat('zh-TW', {
    weekday: 'short',
    timeZone: 'UTC',
});
const label = computed(() => {
    if (!date.value) {
        return null;
    }

    const { year, month, day } = date.value;
    const weekday = weekdayFormat.format(date.value.toDate('UTC'));

    return `${year}/${pad(month)}/${pad(day)}（${weekday}）${hour.value}:${minute.value}`;
});

const selectDate = (selected: DateValue | DateValue[] | undefined) => {
    if (selected && !Array.isArray(selected)) {
        date.value = selected;
    }
};

const clear = () => {
    date.value = undefined;
    open.value = false;
};

const selectClass =
    'h-8 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';
</script>

<template>
    <div>
        <PopoverRoot v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    :id="props.id"
                    type="button"
                    variant="outline"
                    class="w-full justify-start font-normal"
                    :class="{ 'text-muted-foreground': !label }"
                >
                    <CalendarIcon />
                    {{ label ?? '選擇日期時間' }}
                </Button>
            </PopoverTrigger>

            <PopoverPortal>
                <PopoverContent
                    align="start"
                    :side-offset="4"
                    class="z-50 rounded-md border bg-popover p-3 text-popover-foreground shadow-md"
                >
                    <CalendarRoot
                        v-slot="{ grid, weekDays }"
                        :model-value="date"
                        locale="zh-TW"
                        fixed-weeks
                        @update:model-value="selectDate"
                    >
                        <CalendarHeader
                            class="flex items-center justify-between pb-2"
                        >
                            <CalendarPrev as-child>
                                <Button variant="ghost" size="icon-sm">
                                    <ChevronLeft />
                                </Button>
                            </CalendarPrev>
                            <CalendarHeading class="text-sm font-medium" />
                            <CalendarNext as-child>
                                <Button variant="ghost" size="icon-sm">
                                    <ChevronRight />
                                </Button>
                            </CalendarNext>
                        </CalendarHeader>

                        <CalendarGrid
                            v-for="month in grid"
                            :key="month.value.toString()"
                        >
                            <CalendarGridHead>
                                <CalendarGridRow class="flex">
                                    <CalendarHeadCell
                                        v-for="day in weekDays"
                                        :key="day"
                                        class="w-8 text-xs font-normal text-muted-foreground"
                                    >
                                        {{ day }}
                                    </CalendarHeadCell>
                                </CalendarGridRow>
                            </CalendarGridHead>
                            <CalendarGridBody>
                                <CalendarGridRow
                                    v-for="(week, index) in month.rows"
                                    :key="index"
                                    class="mt-1 flex"
                                >
                                    <CalendarCell
                                        v-for="day in week"
                                        :key="day.toString()"
                                        :date="day"
                                    >
                                        <CalendarCellTrigger
                                            :day="day"
                                            :month="month.value"
                                            class="inline-flex size-8 items-center justify-center rounded-md text-sm hover:bg-accent data-outside-view:text-muted-foreground/50 data-selected:bg-primary data-selected:text-primary-foreground data-today:font-semibold"
                                        />
                                    </CalendarCell>
                                </CalendarGridRow>
                            </CalendarGridBody>
                        </CalendarGrid>
                    </CalendarRoot>

                    <div class="mt-3 flex items-center gap-2 border-t pt-3">
                        <span class="text-sm text-muted-foreground">時間</span>
                        <select
                            v-model="hour"
                            :class="selectClass"
                            aria-label="時"
                        >
                            <option v-for="h in hours" :key="h" :value="h">
                                {{ h }}
                            </option>
                        </select>
                        :
                        <select
                            v-model="minute"
                            :class="selectClass"
                            aria-label="分"
                        >
                            <option v-for="m in minutes" :key="m" :value="m">
                                {{ m }}
                            </option>
                        </select>
                    </div>

                    <div class="mt-3 flex justify-end gap-2">
                        <Button
                            v-if="props.clearable && date"
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="clear"
                        >
                            清除
                        </Button>
                        <Button type="button" size="sm" @click="open = false">
                            完成
                        </Button>
                    </div>
                </PopoverContent>
            </PopoverPortal>
        </PopoverRoot>

        <input type="hidden" :name="props.name" :value="value" />
    </div>
</template>
