import { useEffect, useState } from 'react'
import { apiGet, apiPost, apiPut } from '../lib/api'

function PositionModal({ position, onClose, onSaved }) {
  const isEdit = !!position

  const [departments, setDepartments] = useState([])
  const [form, setForm] = useState({
    name: position?.name || '',
    department_id: position?.department_id || '',
  })
  const [error, setError] = useState('')
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    apiGet('/departments')
      .then((res) => setDepartments(Array.isArray(res) ? res : res.data || []))
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
      name: form.name,
      department_id: form.department_id,
    }

    try {
      if (isEdit) {
        await apiPut(`/positions/${position.id}`, payload)
      } else {
        await apiPost('/positions', payload)
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
        <div className="px-6 pt-6 pb-4 flex-shrink-0 border-b border-slate-100">
          <h2 className="text-lg font-bold text-slate-800">
            {isEdit ? 'Edit Position' : 'Add Position'}
          </h2>
        </div>

        <div className="overflow-y-auto px-6 py-4 flex-1">
          {error && (
            <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mb-4">{error}</p>
          )}

          {isEdit && (
            <p className="text-xs text-slate-500 bg-slate-50 px-3 py-2 rounded-lg mb-4">
              Employees who hold this position are updated automatically when you rename it or move it to another department.
            </p>
          )}

          <form id="position-form" onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">Position name</label>
              <input
                name="name"
                value={form.name}
                onChange={handleChange}
                required
                placeholder="e.g. HR Officer"
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              />
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">Department</label>
              <select
                name="department_id"
                value={form.department_id}
                onChange={handleChange}
                required
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              >
                <option value="">— Select department —</option>
                {departments.map((department) => (
                  <option key={department.id} value={department.id}>{department.name}</option>
                ))}
              </select>
            </div>
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
            form="position-form"
            disabled={saving}
            className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition disabled:opacity-50"
          >
            {saving ? 'Saving...' : isEdit ? 'Save Changes' : 'Add Position'}
          </button>
        </div>
      </div>
    </div>
  )
}

export default PositionModal