import { useEffect, useState } from 'react'
import { apiGet, apiPost, apiPut } from '../lib/api'

// Hardcoded to match backend/database/seeders/RoleSeeder.php exactly.
// No GET /roles endpoint exists yet — if roles are added/removed there,
// this list must be updated manually to match.
const AVAILABLE_ROLES = ['admin', 'manager', 'hr', 'accountant', 'supervisor', 'guard', 'client']

function UserModal({ user, onClose, onSaved }) {
  const isEdit = !!user

  const [departments, setDepartments] = useState([])
  const [clients, setClients] = useState([])
  const [form, setForm] = useState({
    name: user?.name || '',
    email: user?.email || '',
    password: '',
    department_id: user?.department_id || '',
    client_id: user?.client_id || '',
    roles: user?.roles?.map((r) => r.name) || [],
  })
  const [error, setError] = useState('')
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    apiGet('/departments')
      .then((res) => setDepartments(Array.isArray(res) ? res : res.data || []))
      .catch(() => {})
    apiGet('/clients')
      .then((res) => setClients(Array.isArray(res) ? res : res.data || []))
      .catch(() => {})
  }, [])

  function handleChange(e) {
    const { name, value } = e.target
    setForm((prev) => ({ ...prev, [name]: value }))
  }

  function toggleRole(roleName) {
    setForm((prev) => ({
      ...prev,
      roles: prev.roles.includes(roleName)
        ? prev.roles.filter((r) => r !== roleName)
        : [...prev.roles, roleName],
    }))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')

    if (form.roles.length === 0) {
      setError('Select at least one role.')
      return
    }

    setSaving(true)

    const payload = {
      name: form.name,
      email: form.email,
      department_id: form.department_id || null,
      client_id: form.client_id || null,
      roles: form.roles,
    }

    if (form.password) {
      payload.password = form.password
    }

    try {
      if (isEdit) {
        await apiPut(`/users/${user.id}`, payload)
      } else {
        await apiPost('/users', payload)
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
            {isEdit ? 'Edit User' : 'Add User'}
          </h2>
        </div>

        <div className="overflow-y-auto px-6 py-4 flex-1">
          {error && (
            <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mb-4">{error}</p>
          )}

          <form id="user-form" onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">Name</label>
              <input
                name="name"
                value={form.name}
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
                required
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              />
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">
                Password {isEdit && <span className="normal-case text-slate-400">(leave blank to keep unchanged)</span>}
              </label>
              <input
                type="password"
                name="password"
                value={form.password}
                onChange={handleChange}
                required={!isEdit}
                minLength={8}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              />
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">Department</label>
              <select
                name="department_id"
                value={form.department_id}
                onChange={handleChange}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              >
                <option value="">— None —</option>
                {departments.map((department) => (
                  <option key={department.id} value={department.id}>{department.name}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">Client (portal access)</label>
              <select
                name="client_id"
                value={form.client_id}
                onChange={handleChange}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              >
                <option value="">— None —</option>
                {clients.map((client) => (
                  <option key={client.id} value={client.id}>{client.name}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 block mb-2">Roles</label>
              <div className="space-y-1.5">
                {AVAILABLE_ROLES.map((roleName) => (
                  <label key={roleName} className="flex items-center gap-2 text-sm text-slate-700 capitalize">
                    <input
                      type="checkbox"
                      checked={form.roles.includes(roleName)}
                      onChange={() => toggleRole(roleName)}
                      className="rounded border-slate-300 text-primary focus:ring-primary"
                    />
                    {roleName}
                  </label>
                ))}
              </div>
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
            form="user-form"
            disabled={saving}
            className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition disabled:opacity-50"
          >
            {saving ? 'Saving...' : isEdit ? 'Save Changes' : 'Add User'}
          </button>
        </div>
      </div>
    </div>
  )
}

export default UserModal