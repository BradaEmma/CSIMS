import { useEffect, useState } from 'react'
import PositionModal from './PositionModal'
import ConfirmModal from './ConfirmModal'
import { apiGet, apiDelete } from '../lib/api'
import { hasRole } from '../lib/can'

function PositionsSection() {
  const canManage = hasRole('admin')

  const [positions, setPositions] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [modalOpen, setModalOpen] = useState(false)
  const [editingPosition, setEditingPosition] = useState(null)
  const [deletingId, setDeletingId] = useState(null)
  const [confirmingPosition, setConfirmingPosition] = useState(null)

  function loadPositions() {
    setLoading(true)
    apiGet('/positions')
      .then((res) => setPositions(Array.isArray(res) ? res : []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadPositions()
  }, [])

  function openAddModal() {
    setEditingPosition(null)
    setModalOpen(true)
  }

  function openEditModal(position) {
    setEditingPosition(position)
    setModalOpen(true)
  }

  function handleSaved() {
    setModalOpen(false)
    setEditingPosition(null)
    setError('')
    loadPositions()
  }

  async function confirmDelete() {
    const position = confirmingPosition
    setConfirmingPosition(null)
    setDeletingId(position.id)
    setError('')
    try {
      await apiDelete(`/positions/${position.id}`)
      loadPositions()
    } catch (err) {
      setError(err.message)
    } finally {
      setDeletingId(null)
    }
  }

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-5 mt-6">
      <div className="flex items-center justify-between mb-4">
        <div>
          <h2 className="text-sm font-bold text-slate-800">
            Employee Positions ({positions.length})
          </h2>
          <p className="text-xs text-slate-500 mt-0.5">
            HR chooses from this list when adding an employee. Each position decides the employee's department.
          </p>
        </div>
        {canManage && (
          <button
            onClick={openAddModal}
            className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition"
          >
            + Add Position
          </button>
        )}
      </div>

      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

      {loading ? (
        <p className="text-slate-500 text-sm">Loading...</p>
      ) : (
        <table className="w-full text-sm">
          <thead>
            <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
              <th className="pb-2">Position</th>
              <th className="pb-2">Department</th>
              {canManage && <th className="pb-2">Actions</th>}
            </tr>
          </thead>
          <tbody>
            {positions.map((position) => (
              <tr key={position.id} className="border-b border-slate-100 last:border-0">
                <td className="py-2.5 font-medium text-slate-700">{position.name}</td>
                <td className="py-2.5 text-slate-600">{position.department?.name || '—'}</td>
                {canManage && (
                  <td className="py-2.5">
                    <button
                      onClick={() => openEditModal(position)}
                      className="text-primary hover:underline text-xs font-medium mr-3"
                    >
                      Edit
                    </button>
                    <button
                      onClick={() => setConfirmingPosition(position)}
                      disabled={deletingId === position.id}
                      className="text-danger hover:underline text-xs font-medium disabled:opacity-50"
                    >
                      {deletingId === position.id ? 'Deleting...' : 'Delete'}
                    </button>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      )}

      {!loading && positions.length === 0 && (
        <p className="text-slate-400 text-sm text-center py-8">No positions found.</p>
      )}

      {modalOpen && canManage && (
        <PositionModal
          position={editingPosition}
          onClose={() => setModalOpen(false)}
          onSaved={handleSaved}
        />
      )}

      {confirmingPosition && canManage && (
        <ConfirmModal
          title="Delete Position"
          message={`Delete ${confirmingPosition.name}? This cannot be undone.`}
          confirmLabel="Delete"
          onConfirm={confirmDelete}
          onCancel={() => setConfirmingPosition(null)}
        />
      )}
    </div>
  )
}

export default PositionsSection