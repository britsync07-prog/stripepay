export type AppNotification = {
    id: string;
    type: string | null;
    title: string;
    message: string;
    url: string | null;
    read: boolean;
    time: string | null;
};

export type NotificationFeed = {
    items: AppNotification[];
    unread: number;
};
