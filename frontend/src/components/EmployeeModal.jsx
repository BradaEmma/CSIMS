import { useEffect, useState } from 'react'
import { apiGet, apiPost, apiPut } from '../lib/api'

function EmployeeModal({ employee, onClose, onSaved }) {
  const isEdit = !!employee

  const [positions, setPositions] = useState([])
  const [form, setForm] = useState({
    name: employee?.name || '',
    phone: employee?.phone || '',
    email: employee?.email || '',
    national_id: employee?.national_id || '',
    position: employee?.position || '',
    hire_date: employee?.hire_date || '',
    status: employee?.status || 'active',
    monthly_salary: employee?.monthly_salary || '',
    nssf_applicable: employee?.nssf_applicable ?? true,
    paye_applicable: employee?.paye_applicable ?? true,
  })
  const [error, setError] = useState('')
  const [saving, setSaving] = useState(false)

  useEffect(() => {
  apiGet('/positions')
      .then((res) => setPositions(Array.isArray(res) ? res : res.data || []))
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
      email: form.email || null,
      national_id: form.national_id || null,
      position: form.position || null,
      hire_date: form.hire_date || null,
    }

    if (isEdit) {
      payload.status = form.status
    }

      if (!isEdit || employee.pay_type === 'monthly') {
      payload.monthly_salary = form.monthly_salary ? Number(form.monthly_salary) : null
      payload.nssf_applicable = !!form.nssf_applicable
      payload.paye_applicable = !!form.paye_applicable
    }

    try {
      if (isEdit) {
        await apiPut(`/employees/${employee.id}`, payload)
      } else {
        await apiPost('/employees', payload)
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
            {isEdit ? 'Edit Employee' : 'Add Employee'}
          </h2>
        </div>

        <div className="overflow-y-auto px-6 py-4 flex-1">
          {error && (
            <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mb-4">{error}</p>
          )}

          <form id="employee-form" onSubmit={handleSubmit} className="space-y-4">
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
              <label className="text-xs font-medium text-slate-500 block mb-1">Email</label>
              <input
                type="email"
                name="email"
                value={form.email}
                onChange={handleChange}
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
              <label className="text-xs font-medium text-slate-500 block mb-1">Position</label>
              <select
                name="position"
                value={form.position}
                onChange={handleChange}
                required
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              >
                <option value="">— Select position —</option>
                {positions.map((p) => (
                  <option key={p.id} value={p.name}>{p.name}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">Department</label>
              <div className="w-full border border-slate-200 bg-slate-50 rounded-lg px-3 py-2 text-sm text-slate-600">
                {positions.find((p) => p.name === form.position)?.department?.name || 'Set automatically from the position'}
              </div>
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">Hire Date</label>
              <input
                type="date"
                name="hire_date"
                value={form.hire_date}
                onChange={handleChange}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              />
            </div>

            {(!isEdit || employee.pay_type === 'monthly') && (
              <div className="border-t border-slate-100 pt-4">
                <p className="text-xs font-bold text-slate-700 uppercase mb-3">Pay</p>
                <div className="space-y-3">
                  <div>
                    <label className="text-xs font-medium text-slate-500 block mb-1">Monthly Salary (TZS)</label>
                    <input
                      type="number"
                      min="0"
                      step="any"
                      name="monthly_salary"
                      value={form.monthly_salary}
                      onChange={handleChange}
                      placeholder="e.g. 500000"
                      className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                    />
                  </div>

                  <label className="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="nssf_applicable" checked={!!form.nssf_applicable} onChange={handleChange} />
                    NSSF applies
                  </label>
                  <label className="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="paye_applicable" checked={!!form.paye_applicable} onChange={handleChange} />
                    PAYE applies
                  </label>
                </div>
              </div>
            )}

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
            form="employee-form"
            disabled={saving}
            className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition disabled:opacity-50"
          >
            {saving ? 'Saving...' : isEdit ? 'Save Changes' : 'Add Employee'}
          </button>
        </div>
      </div>
    </div>
  )
}

export default EmployeeModal