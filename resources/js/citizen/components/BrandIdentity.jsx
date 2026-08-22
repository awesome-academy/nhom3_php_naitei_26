export const BRAND_NAME = 'Cổng Dịch Vụ Công';
export const BRAND_SLOGAN = 'PHỤC VỤ NGƯỜI DÂN';

export function BrandMark({ className = 'h-12 w-12' }) {
    return (
        <img
            alt="Quốc huy Việt Nam"
            className={`shrink-0 object-contain ${className}`}
            src="/emblem-vietnam.svg"
        />
    );
}

export default function BrandIdentity({
    className = '',
    markClassName = 'h-12 w-12',
    nameClassName = 'text-lg text-[#073d7d]',
    sloganClassName = 'text-[11px] text-slate-400',
}) {
    return (
        <span className={`inline-flex min-w-0 items-center gap-3 ${className}`}>
            <BrandMark className={markClassName} />
            <span className="min-w-0">
                <span className={`block whitespace-nowrap font-bold leading-tight ${nameClassName}`}>
                    {BRAND_NAME}
                </span>
                <span className={`mt-0.5 block whitespace-nowrap font-semibold tracking-[0.12em] ${sloganClassName}`}>
                    {BRAND_SLOGAN}
                </span>
            </span>
        </span>
    );
}
