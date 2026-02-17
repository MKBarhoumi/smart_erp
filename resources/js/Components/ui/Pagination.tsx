import { Link } from '@inertiajs/react';

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface PaginationProps {
  links: PaginationLink[];
}

export function Pagination({ links }: PaginationProps) {
  if (links.length <= 3) return null;

  return (
    <div className="flex items-center justify-center gap-1 px-4 py-3">
      {links.map((link, i) => {
        if (!link.url) {
          return (
            <span
              key={i}
              className="px-3 py-1 text-sm rounded-md text-gray-400"
              dangerouslySetInnerHTML={{ __html: link.label }}
            />
          );
        }
        return (
          <Link
            key={i}
            href={link.url}
            className={`px-3 py-1 text-sm rounded-md ${
              link.active
                ? 'bg-indigo-600 text-white'
                : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
            }`}
            dangerouslySetInnerHTML={{ __html: link.label }}
          />
        );
      })}
    </div>
  );
}

export default Pagination;
