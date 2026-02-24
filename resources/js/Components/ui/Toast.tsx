import { useEffect, useState } from 'react';

interface ToastProps {
  type?: 'success' | 'error' | 'warning' | 'info';
  message: string;
  duration?: number;
  onClose?: () => void;
}

const typeStyles = {
  success: 'bg-green-50 border-green-400 text-green-800',
  error: 'bg-red-50 border-red-400 text-red-800',
  warning: 'bg-yellow-50 border-yellow-400 text-yellow-800',
  info: 'bg-blue-50 border-blue-400 text-blue-800',
};

const typeIcons: Record<string, string> = {
  success: '✓',
  error: '✕',
  warning: '⚠',
  info: 'ℹ',
};

export function Toast({ type = 'info', message, duration = 5000, onClose }: ToastProps) {
  const [visible, setVisible] = useState(true);

  useEffect(() => {
    if (duration <= 0) return;

    const timer = setTimeout(() => {
      setVisible(false);
      onClose?.();
    }, duration);

    return () => clearTimeout(timer);
  }, [duration, onClose]);

  if (!visible) return null;

  return (
    <div
      className={`flex items-center gap-3 rounded-lg border-l-4 p-4 shadow-md transition-opacity ${typeStyles[type]}`}
      role="alert"
    >
      <span className="text-lg font-bold">{typeIcons[type]}</span>
      <p className="flex-1 text-sm font-medium">{message}</p>
      <button
        onClick={() => {
          setVisible(false);
          onClose?.();
        }}
        className="ml-2 text-lg font-bold opacity-50 hover:opacity-100"
        aria-label="Fermer"
      >
        ×
      </button>
    </div>
  );
}

export function FlashMessages({ flash }: { flash: { success?: string; error?: string } }) {
  const [messages, setMessages] = useState<Array<{ id: number; type: 'success' | 'error'; message: string }>>([]);

  useEffect(() => {
    const newMessages: typeof messages = [];
    if (flash?.success) {
      newMessages.push({ id: Date.now(), type: 'success', message: flash.success });
    }
    if (flash?.error) {
      newMessages.push({ id: Date.now() + 1, type: 'error', message: flash.error });
    }
    if (newMessages.length > 0) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setMessages((prev) => [...prev, ...newMessages]);
    }
  }, [flash?.success, flash?.error]);

  const removeMessage = (id: number) => {
    setMessages((prev) => prev.filter((m) => m.id !== id));
  };

  if (messages.length === 0) return null;

  return (
    <div className="fixed right-4 top-20 z-50 flex w-96 flex-col gap-2">
      {messages.map((msg) => (
        <Toast key={msg.id} type={msg.type} message={msg.message} onClose={() => removeMessage(msg.id)} />
      ))}
    </div>
  );
}
