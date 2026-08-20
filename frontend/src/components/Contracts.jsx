import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import ContractModal from './ContractModal'
import { apiGet, apiDelete } from '../lib/api'

function StatusBadge({ status }) {
  const styles = {
    active: 'bg-success-bg text-success',
    expired: 'bg-slate-100 text-slate-500',
    terminated: 'bg-danger-bg text-danger',
  }
  return (
    <span className={`text-xs font-semibold px-2.5 py-1 rounded-full capitalize ${styles[status] || 'bg-slate-100 text-slate-600'}`}>
      {status}
    </span>
  )
}

function formatCurrency(value) {
  if (!value) return '—'
  return `TZS ${Number(value).toLocaleString()}`
}

function Contracts() {
  const [contracts, setContracts] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [modalOpen, setModalOpen] = useState(false)
  const [editingContract, setEditingContract] = useState(null)
  const [deletingId, setDeletingId] = useState(null)

  function loadContracts() {
    setLoading(true)
    apiGet('/contracts')
      .then((res) => setContracts(Array.isArray(res) ? res : res.data || []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadContracts()
  }, [])

  function openAddModal() {
    setEditingContract(null)
    setModalOpen(true)
  }

  function openEditModal(contract) {
    setEditingContract(contract)
    setModalOpen(true)
  }

  function handleSaved() {
    setModalOpen(false)
    setEditingContract(null)
    loadContracts()
  }

  async function handleDelete(contract) {
    if (!confirm(`Delete contract ${contract.reference_number}? This cannot be undone.`)) {
      return
    }
    setDeletingId(contract.id)
    try {
      await apiDelete(`/contracts/${contract.id}`)
      loadContracts()
    } catch (err) {
      setError(err.message)
    } finally {
      setDeletingId(null)
    }
  }

  return (
    <AppLayout title="Contracts" subtitle="Manage client service agreements">
      {loading && <p className="text-slate-500">Loading...</p>}

      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

      {!loading && (
        <div className="bg-white rounded-xl border border-slate-200 p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-sm font-bold text-slate-800">
              All Contracts ({contracts.length})
            </h2>
            <button
              onClick={openAddModal}
              className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition"
            >
              + Add Contract
            </button>
          </div>

          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                <th className="pb-2">Reference</th>
                <th className="pb-2">Client</th>
                <th className="pb-2">Start</th>
                <th className="pb-2">End</th>
                <th className="pb-2">Monthly Fee</th>
                <th className="pb-2">Sites</th>
                <th className="pb-2">Status</th>
                <th className="pb-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {contracts.map((contract) => (
                <tr key={contract.id} className="border-b border-slate-100 last:border-0">
                  <td className="py-2.5 font-medium text-slate-700">{contract.reference_number}</td>
                  <td className="py-2.5 text-slate-600">{contract.client?.name || '—'}</td>
                  <td className="py-2.5 text-slate-600">{contract.start_date}</td>
                  <td className="py-2.5 text-slate-600">{contract.end_date || '—'}</td>
                  <td className="py-2.5 text-slate-600">{formatCurrency(contract.monthly_fee)}</td>
                  <td className="py-2.5 text-slate-600">{contract.sites?.length || 0}</td>
                  <td className="py-2.5"><StatusBadge status={contract.status} /></td>
                  <td className="py-2.5">
                    <button
                      onClick={() => openEditModal(contract)}
                      className="text-primary hover:underline text-xs font-medium mr-3"
                    >
                      Edit
                    </button>
                    <button
                      onClick={() => handleDelete(contract)}
                      disabled={deletingId === contract.id}
                      className="text-danger hover:underline text-xs font-medium disabled:opacity-50"
                    >
                      {deletingId === contract.id ? 'Deleting...' : 'Delete'}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {contracts.length === 0 && (
            <p className="text-slate-400 text-sm text-center py-8">No contracts found.</p>
          )}
        </div>
      )}

      {modalOpen && (
        <ContractModal
          contract={editingContract}
          onClose={() => setModalOpen(false)}
          onSaved={handleSaved}
        />
      )}
    </AppLayout>
  )
}

export default Contracts