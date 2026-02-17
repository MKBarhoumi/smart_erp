interface QRCodeDisplayProps {
  content: string;
  size?: number;
  label?: string;
}

/**
 * Displays a CEV QR code from TTN response.
 * If the content is a Base64-encoded image, renders it directly.
 * Otherwise, renders the text content in a styled container.
 */
export function QRCodeDisplay({ content, size = 200, label = 'Code CEV / QR' }: QRCodeDisplayProps) {
  const isBase64Image = content.startsWith('data:image') || /^[A-Za-z0-9+/]+=*$/.test(content.replace(/\s/g, ''));

  if (isBase64Image && !content.startsWith('data:image')) {
    // Assume PNG if raw Base64
    return (
      <div className="flex flex-col items-center gap-2">
        {label && <p className="text-sm font-medium text-gray-600">{label}</p>}
        <img
          src={`data:image/png;base64,${content}`}
          alt="CEV QR Code"
          width={size}
          height={size}
          className="rounded border border-gray-200 p-1"
        />
      </div>
    );
  }

  if (content.startsWith('data:image')) {
    return (
      <div className="flex flex-col items-center gap-2">
        {label && <p className="text-sm font-medium text-gray-600">{label}</p>}
        <img
          src={content}
          alt="CEV QR Code"
          width={size}
          height={size}
          className="rounded border border-gray-200 p-1"
        />
      </div>
    );
  }

  // Fallback: display raw text content
  return (
    <div className="flex flex-col items-center gap-2">
      {label && <p className="text-sm font-medium text-gray-600">{label}</p>}
      <div
        className="flex items-center justify-center rounded border border-gray-300 bg-gray-50 p-4"
        style={{ width: size, minHeight: size / 2 }}
      >
        <p className="break-all text-center font-mono text-xs text-gray-700">{content}</p>
      </div>
    </div>
  );
}
