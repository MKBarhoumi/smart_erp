// Paste to: resources/js/Components/Invoices/TtnLogInspector.tsx
//
// Renders TTN log entries with expandable request/response inspection.
// Improves error visibility for compliance debugging without changing backend logic.

import { useState } from 'react';
import { ChevronDown, ChevronRight, AlertCircle, CheckCircle2 } from 'lucide-react';

interface TtnLog {
  id: number;
  action?: string;
  status_code?: number | null;
  request_payload?: string | null;
  response_payload?: string | null;
  error_message?: string | null;
  created_at: string;
}

interface Props {
  logs: TtnLog[];
}

function isSuccess(code?: number | null): boolean {
  return typeof code === 'number' && code >= 200 && code < 300;
}

function prettyJson(raw: string | null | undefined): string {
  if (!raw) return '';
  try {
    return JSON.stringify(JSON.parse(raw), null, 2);
  } catch {
    return raw;
  }
}

export default function TtnLogInspector({ logs }: Props) {
  const [expanded, setExpanded] = useState<Set<number>>(new Set());

  function toggle(id: number) {
    const next = new Set(expanded);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    setExpanded(next);
  }

  if (logs.length === 0) {
    return (
      <div className="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-500">
        No TTN exchanges recorded yet.
      </div>
    );
  }

  return (
    <div className="overflow-hidden rounded-lg border border-gray-200 bg-white">
      <ul className="divide-y divide-gray-100">
        {logs.map((log) => {
          const ok = isSuccess(log.status_code);
          const isOpen = expanded.has(log.id);
          return (
            <li key={log.id}>
              <button
                type="button"
                onClick={() => toggle(log.id)}
                className="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-gray-50"
                aria-expanded={isOpen}
              >
                {isOpen ? (
                  <ChevronDown className="h-4 w-4 flex-shrink-0 text-gray-400" />
                ) : (
                  <ChevronRight className="h-4 w-4 flex-shrink-0 text-gray-400" />
                )}

                {ok ? (
                  <CheckCircle2 className="h-4 w-4 flex-shrink-0 text-green-600" />
                ) : (
                  <AlertCircle className="h-4 w-4 flex-shrink-0 text-red-600" />
                )}

                <div className="flex flex-1 items-center justify-between gap-3">
                  <div>
                    <div className="text-sm font-medium text-gray-900">
                      {log.action ?? 'TTN exchange'}
                    </div>
                    <div className="text-xs text-gray-500">
                      {new Date(log.created_at).toLocaleString()}
                    </div>
                  </div>
                  <span
                    className={[
                      'rounded-full px-2 py-0.5 font-mono text-xs',
                      ok ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700',
                    ].join(' ')}
                  >
                    {log.status_code ?? 'ERR'}
                  </span>
                </div>
              </button>

              {isOpen && (
                <div className="space-y-3 border-t border-gray-100 bg-gray-50 px-4 py-3">
                  {log.error_message && (
                    <div className="rounded border border-red-200 bg-red-50 p-3">
                      <div className="mb-1 text-xs font-semibold uppercase text-red-700">
                        Error
                      </div>
                      <div className="text-sm text-red-900">{log.error_message}</div>
                    </div>
                  )}

                  {log.request_payload && (
                    <div>
                      <div className="mb-1 text-xs font-semibold uppercase text-gray-600">
                        Request
                      </div>
                      <pre className="max-h-64 overflow-auto rounded bg-white p-3 font-mono text-xs text-gray-800 ring-1 ring-gray-200">
                        {prettyJson(log.request_payload)}
                      </pre>
                    </div>
                  )}

                  {log.response_payload && (
                    <div>
                      <div className="mb-1 text-xs font-semibold uppercase text-gray-600">
                        Response
                      </div>
                      <pre className="max-h-64 overflow-auto rounded bg-white p-3 font-mono text-xs text-gray-800 ring-1 ring-gray-200">
                        {prettyJson(log.response_payload)}
                      </pre>
                    </div>
                  )}
                </div>
              )}
            </li>
          );
        })}
      </ul>
    </div>
  );
}
