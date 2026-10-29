import { Search, X } from 'lucide-react'

function SearchBar({ value, onChange, placeholder = 'Search...' }) {
  return (
    <div className="relative mb-4 max-w-md">
      <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />

      <input
        type="text"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className="w-full border border-slate-300 rounded-lg pl-9 pr-9 py-2 text-sm focus:outline-none focus:border-primary"
      />

      {value && (
        <button
          type="button"
          onClick={() => onChange('')}
          className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
          title="Clear search"
        >
          <X size={15} />
        </button>
      )}
    </div>
  )
}

export default SearchBar