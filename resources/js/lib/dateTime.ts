/**
 * 以指定時區顯示時間（後端傳 ISO 8601 字串，DB 與 app 時區為 UTC）
 */
export function formatDateTime(iso: string, timeZone: string): string {
    return new Intl.DateTimeFormat('zh-TW', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone,
    }).format(new Date(iso));
}
