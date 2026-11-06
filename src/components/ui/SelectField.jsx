/** Pilihan dropdown dengan label di kiri. Terkendali. Props: id, label, value, onChange(value), options [{ value, label }] */
export default function SelectField({ id, label, value, onChange, options }) {
    return (
        <div className="flex items-center gap-1.5">
            <label htmlFor={id} className="text-label-sm font-label-sm text-on-surface-variant">
                {label}
            </label>
            <select
                id={id}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                className="min-h-10 rounded-lg border border-outline-variant/60 bg-surface-bright px-2.5 py-1.5 text-label-md font-label-md text-on-surface focus:outline-none focus:ring-1 focus:ring-primary"
            >
                {options.map((o) => (
                    <option key={o.value} value={o.value}>
                        {o.label}
                    </option>
                ))}
            </select>
        </div>
    );
}
