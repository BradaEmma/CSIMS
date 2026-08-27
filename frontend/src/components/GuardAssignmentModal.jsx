import { useEffect, useState } from 'react'
import { apiGet, apiPost } from '../lib/api'

function GuardAssignmentModal({ onClose, onSaved }) {
  const [guards, setGuards] = useState([])
  const [sites, setSites] = useState([])
  const [guardId, setGuardId] = useState('')
  const [siteId, setSiteId] = useState('')
  const [startDate, setStartDate] = useState('')
  const [error, setError] = useState('')
  const [saving, setSaving] = useState(false)
  const [loadingOptions, setLoadingOptions] = useState(true)

  useEffect(() => {
    Promise.all([apiGet('/guards'), apiGet('/sites')])
      .then(([guardsRes, sitesRes]) => {
        setGuards(Array.isArray(guardsRes) ? guardsRes : [])
        setSites(Array.isArray(sitesRes) ? sitesRes : [])
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoadingOptions(false))
  }, [])

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')

    if (!guardId || !siteId || !startDate) {
      setError('Please fill in all fields.')
      return
    }

    setSaving(true)
    try {
      await apiPost('/assignments/assign', {
        guard_id: Number(guardId),
        site_id: Number(siteId),
        start_date: startDate,
      })
      onSaved()
    } catch (err) {
      setError(err.message)
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-xl w-full max-w-md p-6">
        <h2 className="text-lg font-bold text-slate-800 mb-4">New Guard Assignment</h2>

        {error && (
          <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
        )}

        {loadingOptions ? (
          <p className="text-slate-500 text-sm">Loading options...</p>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-slate-500 mb-1">Guard</label>
              <select
                value={guardId}
                onChange={(e) => setGuardId(e.target.value)}
                className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
              >
                <option value="">Select a guard</option>
                {guards.map((g) => (
                  <option key={g.id} value={g.id}>{g.name}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-500 mb-1">Site</label>
              <select
                value={siteId}
                onChange={(e) => setSiteId(e.target.value)}
                className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
              >
                <option value="">Select a site</option>
                {sites.map((s) => (
                  <option key={s.id} value={s.id}>{s.name}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-500 mb-1">Start Date</label>
              <input
                type="date"
                value={startDate}
                onChange={(e) => setStartDate(e.target.value)}
                className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
              />
            </div>

            <div className="flex justify-end gap-3 pt-2">
              <button
                type="button"
                onClick={onClose}
                className="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={saving}
                className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition disabled:opacity-50"
              >
                {saving ? 'Assigning...' : 'Assign Guard'}
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  )
}

export default GuardAssignmentModal