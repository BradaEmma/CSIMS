import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import GuardModal from './GuardModal'
import { apiGet, apiDelete } from '../lib/api'

function StatusBadge({ status }) {
  const styles = {
    active: 'bg-success-bg text-success',
    inactive: 'bg-slate-100 text-slate-500',
  }
  return (
    <span className={`text-xs font-semibold px-2.5 py-1 rounded-full capitalize ${styles[status] || 'bg-slate-100 text-slate-600'}`}>
      {status}
    </span>
  )
}

function Guards() {
  const [guards, setGuards] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [modalOpen, setModalOpen] = useState(false)
  const [editingGuard, setEditingGuard] = useState(null)
  const [deletingId, setDeletingId] = useState(null)

  function loadGuards() {
    setLoading(true)
    apiGet('/guards')
      .then((res) => setGuards(Array.isArray(res) ? res : []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadGuards()
  }, [])

  function openAddModal() {
    setEditingGuard(null)
    setModalOpen(true)
  }

  function openEditModal(guard) {
    setEditingGuard(guard)
    setModalOpen(true)
  }

  function handleSaved() {
    setModalOpen(false)
    setEditingGuard(null)
    loadGuards()
  }

  async function handleDelete(guard) {
    if (!confirm(`Delete ${guard.name}? This cannot be undone.`)) {
      return
    }
    setDeletingId(guard.id)
    try {
      await apiDelete(`/guards/${guard.id}`)
      loadGuards()
    } catch (err) {
      setError(err.message)
    } finally {
      setDeletingId(null)
    }
  }

  return (
    <AppLayout title="Guards" subtitle="Manage your security personnel">
      {loading && <p className="text-slate-500">Loading...</p>}

      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

      {!loading && (
        <div className="bg-white rounded-xl border border-slate-200 p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-sm font-bold text-slate-800">
              All Guards ({guards.length})
            </h2>
            <button
              onClick={openAddModal}
              className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition"
            >
              + Add Guard
            </button>
          </div>

          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                <th className="pb-2">Name</th>
                <th className="pb-2">Phone</th>
                <th className="pb-2">National ID</th>
                <th className="pb-2">Shift Preference</th>
                <th className="pb-2">Site</th>
                <th className="pb-2">Status</th>
                <th className="pb-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {guards.map((guard) => (
                <tr key={guard.id} className="border-b border-slate-100 last:border-0">
                  <td className="py-2.5 font-medium text-slate-700">{guard.name}</td>
                  <td className="py-2.5 text-slate-600">{guard.phone}</td>
                  <td className="py-2.5 text-slate-600">{guard.national_id || '—'}</td>
                  <td className="py-2.5 text-slate-600 capitalize">{guard.shift_type}</td>
                  <td className="py-2.5 text-slate-600">{guard.site?.name || '—'}</td>
                  <td className="py-2.5"><StatusBadge status={guard.status} /></td>
                  <td className="py-2.5">
                    <button
                      onClick={() => openEditModal(guard)}
                      className="text-primary hover:underline text-xs font-medium mr-3"
                    >
                      Edit
                    </button>
                    <button
                      onClick={() => handleDelete(guard)}
                      disabled={deletingId === guard.id}
                      className="text-danger hover:underline text-xs font-medium disabled:opacity-50"
                    >
                      {deletingId === guard.id ? 'Deleting...' : 'Delete'}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {guards.length === 0 && (
            <p className="text-slate-400 text-sm text-center py-8">No guards found.</p>
          )}
        </div>
      )}

      {modalOpen && (
        <GuardModal
          guard={editingGuard}
          onClose={() => setModalOpen(false)}
          onSaved={handleSaved}
        />
      )}
    </AppLayout>
  )
}

export default Guards