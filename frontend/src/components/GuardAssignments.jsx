import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import GuardAssignmentModal from './GuardAssignmentModal'
import ConfirmModal from './ConfirmModal'
import { apiGet, apiPost } from '../lib/api'

function StatusBadge({ status }) {
  const styles = {
    active: 'bg-success-bg text-success',
    ended: 'bg-slate-100 text-slate-500',
  }
  return (
    <span className={`text-xs font-semibold px-2.5 py-1 rounded-full capitalize ${styles[status] || 'bg-slate-100 text-slate-600'}`}>
      {status}
    </span>
  )
}

function GuardAssignments() {
  const [assignments, setAssignments] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [modalOpen, setModalOpen] = useState(false)
  const [endingId, setEndingId] = useState(null)
  const [confirmingAssignment, setConfirmingAssignment] = useState(null)

  const roles = JSON.parse(localStorage.getItem('csims_roles') || '[]')
  const canAssign = roles.includes('admin')

  function loadAssignments() {
    setLoading(true)
    apiGet('/assignments')
      .then((res) => setAssignments(Array.isArray(res) ? res : []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadAssignments()
  }, [])

  function handleSaved() {
    setModalOpen(false)
    loadAssignments()
  }

  function handleEnd(assignment) {
    setConfirmingAssignment(assignment)
  }

  async function confirmEnd() {
    const assignment = confirmingAssignment
    setConfirmingAssignment(null)
    setEndingId(assignment.id)
    try {
      await apiPost(`/assignments/end/${assignment.id}`, {})
      loadAssignments()
    } catch (err) {
      setError(err.message)
    } finally {
      setEndingId(null)
    }
  }

  return (
    <AppLayout title="Guard Assignments" subtitle="Which guards are eligible to work which sites">
      {loading && <p className="text-slate-500">Loading...</p>}

      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

      {!loading && (
        <div className="bg-white rounded-xl border border-slate-200 p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-sm font-bold text-slate-800">
              All Assignments ({assignments.length})
            </h2>
            {canAssign && (
              <button
                onClick={() => setModalOpen(true)}
                className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition"
              >
                + New Assignment
              </button>
            )}
          </div>

          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                <th className="pb-2">Guard</th>
                <th className="pb-2">Site</th>
                <th className="pb-2">Start Date</th>
                <th className="pb-2">End Date</th>
                <th className="pb-2">Status</th>
                {canAssign && <th className="pb-2">Actions</th>}
              </tr>
            </thead>
            <tbody>
              {assignments.map((a) => (
                <tr key={a.id} className="border-b border-slate-100 last:border-0">
                  <td className="py-2.5 font-medium text-slate-700">{a.assigned_guard?.name || '—'}</td>
                  <td className="py-2.5 text-slate-600">{a.site?.name || '—'}</td>
                  <td className="py-2.5 text-slate-600">{a.start_date}</td>
                  <td className="py-2.5 text-slate-600">{a.end_date || '—'}</td>
                  <td className="py-2.5"><StatusBadge status={a.status} /></td>
                  {canAssign && (
                    <td className="py-2.5">
                      {a.status === 'active' && (
                        <button
                          onClick={() => handleEnd(a)}
                          disabled={endingId === a.id}
                          className="text-danger hover:underline text-xs font-medium disabled:opacity-50"
                        >
                          {endingId === a.id ? 'Ending...' : 'End'}
                        </button>
                      )}
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>

          {assignments.length === 0 && (
            <p className="text-slate-400 text-sm text-center py-8">No assignments found.</p>
          )}
        </div>
      )}

          {modalOpen && canAssign && (
        <GuardAssignmentModal
          onClose={() => setModalOpen(false)}
          onSaved={handleSaved}
        />
      )}

      {confirmingAssignment && (
        <ConfirmModal
          title="End Assignment"
          message={`End assignment for ${confirmingAssignment.assigned_guard?.name || 'this guard'}?`}
          confirmLabel="End Assignment"
          onConfirm={confirmEnd}
          onCancel={() => setConfirmingAssignment(null)}
        />
      )}
    </AppLayout>
  )
}

export default GuardAssignments