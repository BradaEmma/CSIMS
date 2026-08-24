import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import ApprovalActionModal from './ApprovalActionModal'
import ApprovalDetailModal from './ApprovalDetailModal'
import { apiGet } from '../lib/api'

function Approvals() {
  const [requests, setRequests] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [actionState, setActionState] = useState(null) // { request, action }
  const [detailId, setDetailId] = useState(null)

  function loadPending() {
    setLoading(true)
    apiGet('/approvals/pending')
      .then((res) => setRequests(res.data || []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadPending()
  }, [])

  function handleSaved() {
    setActionState(null)
    loadPending()
  }

  return (
    <AppLayout title="Approvals" subtitle="Requests awaiting your action">
      {loading && <p className="text-slate-500">Loading...</p>}

      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

      {!loading && (
        <div className="bg-white rounded-xl border border-slate-200 p-5">
          <h2 className="text-sm font-bold text-slate-800 mb-4">
            Pending My Action ({requests.length})
          </h2>

          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                <th className="pb-2">Submitted</th>
                <th className="pb-2">Type</th>
                <th className="pb-2">Amount</th>
                <th className="pb-2">Submitted By</th>
                <th className="pb-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {requests.map((request) => (
                <tr key={request.id} className="border-b border-slate-100 last:border-0">
                  <td className="py-2.5 text-slate-600">
                    {new Date(request.created_at).toLocaleDateString()}
                  </td>
                  <td className="py-2.5 font-medium text-slate-700 capitalize">
                    {request.approvable_type?.replace('_', ' ')}
                  </td>
                  <td className="py-2.5 text-slate-600">
                    {request.amount ? `TZS ${Number(request.amount).toLocaleString()}` : '—'}
                  </td>
                  <td className="py-2.5 text-slate-600">{request.submitter?.name || '—'}</td>
                  <td className="py-2.5">
                    <div className="flex gap-3">
                      <button
                        onClick={() => setActionState({ request, action: 'approve' })}
                        className="text-success hover:underline text-xs font-medium"
                      >
                        Approve
                      </button>
                      <button
                        onClick={() => setActionState({ request, action: 'reject' })}
                        className="text-danger hover:underline text-xs font-medium"
                      >
                        Reject
                      </button>
                      <button
                        onClick={() => setActionState({ request, action: 'return' })}
                        className="text-warning hover:underline text-xs font-medium"
                      >
                        Return
                      </button>
                      <button
                        onClick={() => setDetailId(request.id)}
                        className="text-slate-500 hover:underline text-xs font-medium"
                      >
                        View
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {requests.length === 0 && (
            <p className="text-slate-400 text-sm text-center py-8">No pending approvals.</p>
          )}
        </div>
      )}

      {actionState && (
        <ApprovalActionModal
          request={actionState.request}
          action={actionState.action}
          onClose={() => setActionState(null)}
          onSaved={handleSaved}
        />
      )}

      {detailId && (
        <ApprovalDetailModal requestId={detailId} onClose={() => setDetailId(null)} />
      )}
    </AppLayout>
  )
}

export default Approvals