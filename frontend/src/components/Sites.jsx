import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import SiteModal from './SiteModal'
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

function Sites() {
  const [sites, setSites] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [modalOpen, setModalOpen] = useState(false)
  const [editingSite, setEditingSite] = useState(null)
  const [deletingId, setDeletingId] = useState(null)

  function loadSites() {
    setLoading(true)
    apiGet('/sites')
      .then((res) => setSites(Array.isArray(res) ? res : []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadSites()
  }, [])

  function openAddModal() {
    setEditingSite(null)
    setModalOpen(true)
  }

  function openEditModal(site) {
    setEditingSite(site)
    setModalOpen(true)
  }

  function handleSaved() {
    setModalOpen(false)
    setEditingSite(null)
    loadSites()
  }

  async function handleDelete(site) {
    if (!confirm(`Delete ${site.name}? This cannot be undone.`)) {
      return
    }
    setDeletingId(site.id)
    try {
      await apiDelete(`/sites/${site.id}`)
      loadSites()
    } catch (err) {
      setError(err.message)
    } finally {
      setDeletingId(null)
    }
  }

  return (
    <AppLayout title="Sites" subtitle="Manage your client sites and coverage needs">
      {loading && <p className="text-slate-500">Loading...</p>}

      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

      {!loading && (
        <div className="bg-white rounded-xl border border-slate-200 p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-sm font-bold text-slate-800">
              All Sites ({sites.length})
            </h2>
            <button
              onClick={openAddModal}
              className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition"
            >
              + Add Site
            </button>
          </div>

          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                <th className="pb-2">Name</th>
                <th className="pb-2">Zone</th>
                <th className="pb-2">Location</th>
                <th className="pb-2">Morning</th>
                <th className="pb-2">Night</th>
                <th className="pb-2">Status</th>
                <th className="pb-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {sites.map((site) => (
                <tr key={site.id} className="border-b border-slate-100 last:border-0">
                  <td className="py-2.5 font-medium text-slate-700">{site.name}</td>
                  <td className="py-2.5 text-slate-600">{site.zone}</td>
                  <td className="py-2.5 text-slate-600">{site.location || '—'}</td>
                  <td className="py-2.5 text-slate-600">{site.morning_guards_required}</td>
                  <td className="py-2.5 text-slate-600">{site.night_guards_required}</td>
                  <td className="py-2.5"><StatusBadge status={site.status} /></td>
                  <td className="py-2.5">
                    <button
                      onClick={() => openEditModal(site)}
                      className="text-primary hover:underline text-xs font-medium mr-3"
                    >
                      Edit
                    </button>
                    <button
                      onClick={() => handleDelete(site)}
                      disabled={deletingId === site.id}
                      className="text-danger hover:underline text-xs font-medium disabled:opacity-50"
                    >
                      {deletingId === site.id ? 'Deleting...' : 'Delete'}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {sites.length === 0 && (
            <p className="text-slate-400 text-sm text-center py-8">No sites found.</p>
          )}
        </div>
      )}

      {modalOpen && (
        <SiteModal
          site={editingSite}
          onClose={() => setModalOpen(false)}
          onSaved={handleSaved}
        />
      )}
    </AppLayout>
  )
}

export default Sites