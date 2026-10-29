export type EventStatus = 'draft' | 'published' | 'cancelled';

export const eventStatusLabels: Record<EventStatus, string> = {
    draft: '草稿',
    published: '已發布',
    cancelled: '已取消',
};

export const eventStatusVariants: Record<
    EventStatus,
    'secondary' | 'default' | 'destructive'
> = {
    draft: 'secondary',
    published: 'default',
    cancelled: 'destructive',
};
