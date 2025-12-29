'use client';

import { useTranslations } from 'next-intl';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { formatDistanceToNow } from 'date-fns';
import { ar } from 'date-fns/locale';
import { toast } from 'sonner';
import { Bell, Check, CheckCheck, Trash2 } from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import api from '@/lib/api/client';
import { cn } from '@/lib/utils';
import type { Notification, ApiResponse } from '@/types';

export default function NotificationsPage() {
  const t = useTranslations();
  const queryClient = useQueryClient();

  // Fetch notifications
  const { data: notifications, isLoading } = useQuery<ApiResponse<Notification[]>>({
    queryKey: ['notifications'],
    queryFn: async () => {
      const response = await api.get('/notifications');
      return response.data;
    },
  });

  // Mark as read mutation
  const markReadMutation = useMutation({
    mutationFn: async (id: number) => {
      await api.post(`/notifications/${id}/read`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notifications'] });
    },
  });

  // Mark all as read mutation
  const markAllReadMutation = useMutation({
    mutationFn: async () => {
      await api.post('/notifications/read-all');
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notifications'] });
      toast.success(t('common.success'));
    },
  });

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/notifications/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notifications'] });
      toast.success(t('common.success'));
    },
  });

  const unreadCount = notifications?.data?.filter((n) => !n.read_at).length || 0;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">{t('notifications.title')}</h1>
          {unreadCount > 0 && (
            <p className="text-sm text-gray-500 mt-1">
              {unreadCount} {t('notifications.unread')}
            </p>
          )}
        </div>
        {unreadCount > 0 && (
          <Button
            variant="outline"
            onClick={() => markAllReadMutation.mutate()}
            disabled={markAllReadMutation.isPending}
          >
            <CheckCheck className="h-4 w-4 me-2" />
            {t('notifications.markAllRead')}
          </Button>
        )}
      </div>

      {isLoading ? (
        <div className="space-y-4">
          {[1, 2, 3].map((i) => (
            <Skeleton key={i} className="h-24" />
          ))}
        </div>
      ) : notifications?.data && notifications.data.length > 0 ? (
        <div className="space-y-3">
          {notifications.data.map((notification) => (
            <Card
              key={notification.id}
              className={cn(
                'transition-all',
                !notification.read_at && 'border-primary/50 bg-primary/5'
              )}
            >
              <CardContent className="p-4">
                <div className="flex items-start justify-between gap-4">
                  <div className="flex items-start gap-3">
                    <div
                      className={cn(
                        'h-10 w-10 rounded-full flex items-center justify-center flex-shrink-0',
                        notification.read_at
                          ? 'bg-gray-100'
                          : 'bg-primary/10'
                      )}
                    >
                      <Bell
                        className={cn(
                          'h-5 w-5',
                          notification.read_at
                            ? 'text-gray-500'
                            : 'text-primary'
                        )}
                      />
                    </div>
                    <div>
                      <p className="font-medium">{notification.title}</p>
                      <p className="text-sm text-gray-500 mt-1">
                        {notification.body}
                      </p>
                      <p className="text-xs text-gray-400 mt-2">
                        {formatDistanceToNow(new Date(notification.created_at), {
                          addSuffix: true,
                          locale: ar,
                        })}
                      </p>
                    </div>
                  </div>
                  <div className="flex gap-1">
                    {!notification.read_at && (
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => markReadMutation.mutate(notification.id)}
                        title={t('notifications.markAllRead')}
                      >
                        <Check className="h-4 w-4" />
                      </Button>
                    )}
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => deleteMutation.mutate(notification.id)}
                      className="text-red-500 hover:text-red-600 hover:bg-red-50"
                      title={t('common.delete')}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <div className="text-center py-12">
          <Bell className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-500">{t('notifications.noNotifications')}</p>
        </div>
      )}
    </div>
  );
}
