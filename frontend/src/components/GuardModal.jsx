import { useEffect, useState } from 'react'
import { apiGet, apiPost, apiPut } from '../lib/api'
import GuardDocuments from './GuardDocuments'
import GuardPhotoUpload from './GuardPhotoUpload'

function GuardModal({ guard, onClose, onSaved }) {
  const isEdit = !!guard

  const [sites, setSites] = useState([])
  const [form, setForm] = useState({
    name: guard?.name || '',
    phone: guard?.phone || '',
    national_id: guard?.national_id || '',
    shift_type: guard?.shift_type || 'morning',
    site_id: guard?.site_id || '',
    status: guard?.status || 'active',
    daily_rate: guard?.daily_rate || '',
    nssf_applicable: guard?.nssf_applicable ?? false,
    paye_applicable: guard?.paye_applicable ?? false,
  })
  const [error, setError] = useState('')
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    apiGet('/sites')
      .then((res) => setSites(Array.isArray(res) ? res : res.data || []))
      .catch(() => {})
  }, [])

  function handleChange(e) {
    const { name, value, type, checked } = e.target
    setForm((prev) => ({ ...prev, [name]: type === 'checkbox' ? checked : value }))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')
    setSaving(true)

    const payload = {
      name: form.name,
      phone: form.phone,
      national_id: form.national_id || null,
      shift_type: form.shift_type,
      site_id: form.site_id || null,
      daily_rate: form.daily_rate ? Number(form.daily_rate) : null,
      nssf_applicable: form.nssf_applicable,
      paye_applicable: form.paye_applicable,
    }

    if (isEdit) {
      payload.status = form.status
    }

    try {
      if (isEdit) {
        await apiPut(`/guards/${guard.id}`, payload)
      } else {
        await apiPost('/guards', payload)
      }
      onSaved()
    } catch (err) {
      setError(err.message)
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
      <div className="bg-white rounded-xl w-full max-w-md max-h-[85vh] flex flex-col">
        <div className="px-6 pt-6 pb-4 flex-shrink-0 border-b border-slate-100 flex flex-col items-center">
          <GuardPhotoUpload guardId={guard?.id} guardName={form.name || guard?.name} />
          <h2 className="text-lg font-bold text-slate-800 mt-3">
            {isEdit ? 'Edit Guard' : 'Add Guard'}
          </h2>
        </div>

        <div className="overflow-y-auto px-6 py-4 flex-1">
          {error && (
            <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mb-4">{error}</p>
          )}

          <form id="guard-form" onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Full Name</label>
            <input
              name="name"
              value={form.name}
              onChange={handleChange}
              required
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            />
          </div>

          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Phone</label>
            <input
              name="phone"
              value={form.phone}
              onChange={handleChange}
              required
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            />
          </div>

          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">National ID</label>
            <input
              name="national_id"
              value={form.national_id}
              onChange={handleChange}
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            />
          </div>

          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Shift Preference</label>
            <select
              name="shift_type"
              value={form.shift_type}
              onChange={handleChange}
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            >
              <option value="morning">Morning</option>
              <option value="night">Night</option>
              <option value="either">Either</option>
            </select>
          </div>

          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Daily Rate (TZS)</label>
            <input
              type="number"
              min="0"
              step="any"
              name="daily_rate"
              value={form.daily_rate}
              onChange={handleChange}
              required
              placeholder="e.g. 15000"
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            />
          </div>

          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Site</label>
            <select
              name="site_id"
              value={form.site_id}
              onChange={handleChange}
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            >
              <option value="">— None —</option>
              {sites.map((site) => (
                <option key={site.id} value={site.id}>{site.name}</option>
              ))}
            </select>
          </div>

          <div className="flex items-center gap-6 pt-1">
            <label className="flex items-center gap-2 text-sm text-slate-700">
              <input
                type="checkbox"
                name="nssf_applicable"
                checked={form.nssf_applicable}
                onChange={handleChange}
                className="rounded border-slate-300 text-primary focus:ring-primary"
              />
              NSSF Applicable
            </label>
            <label className="flex items-center gap-2 text-sm text-slate-700">
              <input
                type="checkbox"
                name="paye_applicable"
                checked={form.paye_applicable}
                onChange={handleChange}
                className="rounded border-slate-300 text-primary focus:ring-primary"
              />
              PAYE Applicable
            </label>
          </div>

          <div className="border-t border-slate-100 pt-4">
            <GuardDocuments guardId={guard?.id} />
          </div>

          {isEdit && (
            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">Status</label>
              <select
                name="status"
                value={form.status}
                onChange={handleChange}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          )}

          </form>
        </div>

        <div className="px-6 py-4 flex-shrink-0 border-t border-slate-100 flex justify-end gap-3">
          <button
            type="button"
            onClick={onClose}
            className="text-sm font-medium text-slate-600 px-4 py-2 rounded-lg hover:bg-slate-100"
          >
            Cancel
          </button>
          <button
            type="submit"
            form="guard-form"
            disabled={saving}
            className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition disabled:opacity-50"
          >
            {saving ? 'Saving...' : isEdit ? 'Save Changes' : 'Add Guard'}
          </button>
        </div>
      </div>
    </div>
  )
}

export default GuardModal