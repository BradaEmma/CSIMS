import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import UserModal from './UserModal'
import { apiGet } from '../lib/api'

function Users() {
  const roles = JSON.parse(localStorage.getItem('csims_roles') || '[]')
  const canManage = roles.includes('admin')

  const [users, setUsers] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [modalOpen, setModalOpen] = useState(false)
  const [editingUser, setEditingUser] = useState(null)

  function loadUsers() {
    setLoading(true)
    apiGet('/users')
      .then((res) => setUsers(Array.isArray(res) ? res : []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadUsers()
  }, [])

  function openAddModal() {
    setEditingUser(null)
    setModalOpen(true)
  }

  function openEditModal(user) {
    setEditingUser(user)
    setModalOpen(true)
  }

  function handleSaved() {
    setModalOpen(false)
    setEditingUser(null)
    loadUsers()
  }

  return (
    <AppLayout title="Users" subtitle="Manage system users, roles, and department access">
      {loading && <p className="text-slate-500">Loading...</p>}

      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

      {!loading && (
        <div className="bg-white rounded-xl border border-slate-200 p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-sm font-bold text-slate-800">
              All Users ({users.length})
            </h2>
            {canManage && (
              <button
                onClick={openAddModal}
                className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition"
              >
                + Add User
              </button>
            )}
          </div>

          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                <th className="pb-2">Name</th>
                <th className="pb-2">Email</th>
                <th className="pb-2">Department</th>
                <th className="pb-2">Roles</th>
                {canManage && <th className="pb-2">Actions</th>}
              </tr>
            </thead>
            <tbody>
              {users.map((user) => (
                <tr key={user.id} className="border-b border-slate-100 last:border-0">
                  <td className="py-2.5 font-medium text-slate-700">{user.name}</td>
                  <td className="py-2.5 text-slate-600">{user.email}</td>
                  <td className="py-2.5 text-slate-600">{user.department?.name || '—'}</td>
                  <td className="py-2.5 text-slate-600">
                    {user.roles?.map((r) => r.name).join(', ') || '—'}
                  </td>
                  {canManage && (
                    <td className="py-2.5">
                      <button
                        onClick={() => openEditModal(user)}
                        className="text-primary hover:underline text-xs font-medium"
                      >
                        Edit
                      </button>
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>

          {users.length === 0 && (
            <p className="text-slate-400 text-sm text-center py-8">No users found.</p>
          )}
        </div>
      )}

      {modalOpen && canManage && (
        <UserModal
          user={editingUser}
          onClose={() => setModalOpen(false)}
          onSaved={handleSaved}
        />
      )}
    </AppLayout>
  )
}

export default Users