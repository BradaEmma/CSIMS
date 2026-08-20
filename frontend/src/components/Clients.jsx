import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import ClientModal from './ClientModal'
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

function Clients() {
  const [clients, setClients] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [modalOpen, setModalOpen] = useState(false)
  const [editingClient, setEditingClient] = useState(null)
  const [deletingId, setDeletingId] = useState(null)

  function loadClients() {
    setLoading(true)
    apiGet('/clients')
      .then((res) => setClients(Array.isArray(res) ? res : res.data || []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadClients()
  }, [])

  function openAddModal() {
    setEditingClient(null)
    setModalOpen(true)
  }

  function openEditModal(client) {
    setEditingClient(client)
    setModalOpen(true)
  }

  function handleSaved() {
    setModalOpen(false)
    setEditingClient(null)
    loadClients()
  }

  async function handleDelete(client) {
    if (!confirm(`Delete ${client.name}? This cannot be undone.`)) {
      return
    }
    setDeletingId(client.id)
    try {
      await apiDelete(`/clients/${client.id}`)
      loadClients()
    } catch (err) {
      setError(err.message)
    } finally {
      setDeletingId(null)
    }
  }

  return (
    <AppLayout title="Clients" subtitle="Manage client organizations">
      {loading && <p className="text-slate-500">Loading...</p>}

      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

      {!loading && (
        <div className="bg-white rounded-xl border border-slate-200 p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-sm font-bold text-slate-800">
              All Clients ({clients.length})
            </h2>
            <button
              onClick={openAddModal}
              className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition"
            >
              + Add Client
            </button>
          </div>

          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                <th className="pb-2">Name</th>
                <th className="pb-2">Contact Person</th>
                <th className="pb-2">Phone</th>
                <th className="pb-2">Email</th>
                <th className="pb-2">Status</th>
                <th className="pb-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {clients.map((client) => (
                <tr key={client.id} className="border-b border-slate-100 last:border-0">
                  <td className="py-2.5 font-medium text-slate-700">{client.name}</td>
                  <td className="py-2.5 text-slate-600">{client.contact_person || '—'}</td>
                  <td className="py-2.5 text-slate-600">{client.phone || '—'}</td>
                  <td className="py-2.5 text-slate-600">{client.email || '—'}</td>
                  <td className="py-2.5"><StatusBadge status={client.status} /></td>
                  <td className="py-2.5">
                    <button
                      onClick={() => openEditModal(client)}
                      className="text-primary hover:underline text-xs font-medium mr-3"
                    >
                      Edit
                    </button>
                    <button
                      onClick={() => handleDelete(client)}
                      disabled={deletingId === client.id}
                      className="text-danger hover:underline text-xs font-medium disabled:opacity-50"
                    >
                      {deletingId === client.id ? 'Deleting...' : 'Delete'}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {clients.length === 0 && (
            <p className="text-slate-400 text-sm text-center py-8">No clients found.</p>
          )}
        </div>
      )}

      {modalOpen && (
        <ClientModal
          client={editingClient}
          onClose={() => setModalOpen(false)}
          onSaved={handleSaved}
        />
      )}
    </AppLayout>
  )
}

export default Clients