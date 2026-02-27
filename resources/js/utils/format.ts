/**
 * Utility functions for formatting values
 */

/**
 * Format a number with thousand separators and optional decimal places
 * @param value - The number or string to format
 * @param decimals - Number of decimal places (default: 3 for TND currency)
 * @returns Formatted string with thousand separators
 */
export function formatNumber(value: string | number | null | undefined, decimals: number = 3): string {
    if (value === null || value === undefined || value === '') {
        return '0'.padEnd(decimals > 0 ? decimals + 2 : 1, decimals > 0 ? '.000'.substring(0, decimals + 1) : '');
    }
    
    const num = typeof value === 'string' ? parseFloat(value) : value;
    
    if (isNaN(num)) {
        return '0'.padEnd(decimals > 0 ? decimals + 2 : 1, decimals > 0 ? '.000'.substring(0, decimals + 1) : '');
    }
    
    return num.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

/**
 * Format a number as TND currency with thousand separators
 * @param value - The number or string to format
 * @returns Formatted string with "TND" suffix
 */
export function formatTND(value: string | number | null | undefined): string {
    return `${formatNumber(value, 3)} TND`;
}

/**
 * Format a percentage value
 * @param value - The number or string to format
 * @param decimals - Number of decimal places (default: 2)
 * @returns Formatted percentage string
 */
export function formatPercent(value: string | number | null | undefined, decimals: number = 2): string {
    if (value === null || value === undefined || value === '') {
        return '0%';
    }
    
    const num = typeof value === 'string' ? parseFloat(value) : value;
    
    if (isNaN(num)) {
        return '0%';
    }
    
    return `${num.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    })}%`;
}

/**
 * Format a date string to a locale-friendly format
 * @param dateString - ISO date string or date-like value
 * @param options - Intl.DateTimeFormat options
 * @returns Formatted date string
 */
export function formatDate(
    dateString: string | Date | null | undefined,
    options: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'short', day: 'numeric' }
): string {
    if (!dateString) return '—';
    
    try {
        const date = typeof dateString === 'string' ? new Date(dateString) : dateString;
        return date.toLocaleDateString('en-US', options);
    } catch {
        return '—';
    }
}

/**
 * Format a datetime string to a locale-friendly format
 * @param dateString - ISO datetime string
 * @returns Formatted datetime string
 */
export function formatDateTime(dateString: string | null | undefined): string {
    if (!dateString) return '—';
    
    try {
        return new Date(dateString).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return '—';
    }
}
