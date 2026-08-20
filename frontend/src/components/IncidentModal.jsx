import { useEffect, useState } from 'react'
import { apiGet, apiPost } from '../lib/api'

function IncidentModal({ onClose, onSaved }) {
  const [incidentTypes, setIncidentTypes] = useState([])
  const [sites, setSites] = useState([])
  const [form, setForm] = useState({
    site_id: '',
    incident_type_id: '',
    severity: 'low',
    description: '',
    occurred_at: '',
  })
  const [error, setError] = useState('')
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    Promise.all([apiGet('/incident-types'), apiGet('/sites')])
      .then(([types, sitesRes]) => {
        setIncidentTypes(types.data || [])
        setSites(Array.isArray(sitesRes) ? sitesRes : [])
      })
      .catch((err) => setError(err.message))
  }, [])

  function handleChange(e) {
    const { name, value } = e.target
    setForm((prev) => ({ ...prev, [name]: value }))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')
    setSaving(true)

    const payload = {
      site_id: Number(form.site_id),
      incident_type_id: Number(form.incident_type_id),
      severity: form.severity,
      description: form.description,
      occurred_at: form.occurred_at || null,
    }

    try {
      await apiPost('/incidents', payload)
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
        <h2 className="text-lg font-bold text-slate-800 mb-4">Report Incident</h2>

        {error && (
          <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mb-4">{error}</p>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Site</label>
            <select
              name="site_id"
              value={form.site_id}
              onChange={handleChange}
              required
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            >
              <option value="">Select a site</option>
              {sites.map((site) => (
                <option key={site.id} value={site.id}>{site.name}</option>
              ))}
            </select>
          </div>

          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Incident Type</label>
            <select
              name="incident_type_id"
              value={form.incident_type_id}
              onChange={handleChange}
              required
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            >
              <option value="">Select a type</option>
              {incidentTypes.map((type) => (
                <option key={type.id} value={type.id}>{type.name}</option>
              ))}
            </select>
          </div>

          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Severity</label>
            <select
              name="severity"
              value={form.severity}
              onChange={handleChange}
              required
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            >
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="critical">Critical</option>
            </select>
          </div>

          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Occurred At</label>
            <input
              type="datetime-local"
              name="occurred_at"
              value={form.occurred_at}
              onChange={handleChange}
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            />
          </div>

          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Description</label>
            <textarea
              name="description"
              value={form.description}
              onChange={handleChange}
              rows={3}
              required
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
              className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition disabled:opacity-50"
            >
              {saving ? 'Saving...' : 'Report Incident'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default IncidentModal