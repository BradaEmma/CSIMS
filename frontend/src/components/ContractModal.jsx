import { useEffect, useState } from 'react'
import { apiGet, apiPost, apiPut } from '../lib/api'
import DocumentUpload from './DocumentUpload'

function ContractModal({ contract, onClose, onSaved }) {
  const isEdit = !!contract

  const [clients, setClients] = useState([])
  const [form, setForm] = useState({
    client_id: contract?.client_id || '',
    reference_number: contract?.reference_number || '',
    start_date: contract?.start_date || '',
    end_date: contract?.end_date || '',
    monthly_fee: contract?.monthly_fee || '',
    terms: contract?.terms || '',
    status: contract?.status || 'active',
  })
  const [error, setError] = useState('')
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    apiGet('/clients')
      .then((res) => setClients(Array.isArray(res) ? res : res.data || []))
      .catch(() => {})
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
      reference_number: form.reference_number,
      start_date: form.start_date,
      end_date: form.end_date || null,
      monthly_fee: form.monthly_fee ? Number(form.monthly_fee) : null,
      terms: form.terms || null,
    }

    if (isEdit) {
      payload.status = form.status
    } else {
      payload.client_id = Number(form.client_id)
    }

    try {
      if (isEdit) {
        await apiPut(`/contracts/${contract.id}`, payload)
      } else {
        await apiPost('/contracts', payload)
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
      <div className="bg-white rounded-xl w-full max-w-md p-6">
        <h2 className="text-lg font-bold text-slate-800 mb-4">
          {isEdit ? 'Edit Contract' : 'Add Contract'}
        </h2>

        {error && (
          <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mb-4">{error}</p>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          {!isEdit && (
            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">Client</label>
              <select
                name="client_id"
                value={form.client_id}
                onChange={handleChange}
                required
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              >
                <option value="">Select a client</option>
                {clients.map((c) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </select>
            </div>
          )}

          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Reference Number</label>
            <input
              name="reference_number"
              value={form.reference_number}
              onChange={handleChange}
              required
              placeholder="e.g. CUBS-2026-001"
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">Start Date</label>
              <input
                type="date"
                name="start_date"
                value={form.start_date}
                onChange={handleChange}
                required
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              />
            </div>
            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">End Date</label>
              <input
                type="date"
                name="end_date"
                value={form.end_date}
                onChange={handleChange}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              />
            </div>
          </div>

          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Monthly Fee (TZS)</label>
            <input
              type="number"
              min="0"
              step="any"
              name="monthly_fee"
              value={form.monthly_fee}
              onChange={handleChange}
              placeholder="e.g. 350000"
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            />
          </div>

          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Terms</label>
            <textarea
              name="terms"
              value={form.terms}
              onChange={handleChange}
              rows={3}
              placeholder="e.g. 2 guards per shift, 24-hour coverage"
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            />
          </div>

          <DocumentUpload
            documentableType="contract"
            documentableId={contract?.id}
            docType="signed_contract"
            label="Signed Contract"
          />

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
                <option value="expired">Expired</option>
                <option value="terminated">Terminated</option>
              </select>
            </div>
          )}

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
              {saving ? 'Saving...' : isEdit ? 'Save Changes' : 'Add Contract'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default ContractModal