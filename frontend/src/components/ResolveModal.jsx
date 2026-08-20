import { useState } from 'react'
import { apiPost } from '../lib/api'

function ResolveModal({ incident, onClose, onSaved }) {
  const [notes, setNotes] = useState('')
  const [error, setError] = useState('')
  const [saving, setSaving] = useState(false)

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')
    setSaving(true)

    try {
      await apiPost(`/incidents/${incident.id}/resolve`, { resolution_notes: notes })
      onSaved()
    } catch (err) {
      setError(err.message)
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
      <div className="bg-white rounded-xl w-full max-w-md p-6">
        <h2 className="text-lg font-bold text-slate-800 mb-1">Resolve Incident</h2>
        <p className="text-sm text-slate-500 mb-4">{incident.description}</p>

        {error && (
          <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mb-4">{error}</p>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Resolution Notes</label>
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              rows={4}
              required
              placeholder="Describe how this incident was resolved..."
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            />
          </div>

          <div className="flex justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={onClose}
              className="text-sm font-medium text-slate-600 px-4 py-2 rounded-lg hover:bg-slate-100"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={saving}
              className="bg-success text-white text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition disabled:opacity-50"
            >
              {saving ? 'Resolving...' : 'Mark as Resolved'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default ResolveModal