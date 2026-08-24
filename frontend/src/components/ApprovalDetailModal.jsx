import { useEffect, useState } from 'react'
import { apiGet } from '../lib/api'

function ApprovalDetailModal({ requestId, onClose }) {
  const [detail, setDetail] = useState(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    apiGet(`/approvals/${requestId}`)
      .then((res) => setDetail(res.data))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }, [requestId])

  return (
    <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
      <div className="bg-white rounded-xl w-full max-w-lg p-6 max-h-[80vh] overflow-y-auto">
        <h2 className="text-lg font-bold text-slate-800 mb-4">Request History</h2>

        {loading && <p className="text-slate-500 text-sm">Loading...</p>}
        {error && (
          <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mb-4">{error}</p>
        )}

        {detail && (
          <div className="space-y-4">
            <div className="bg-slate-50 rounded-lg p-4 text-sm space-y-1">
              <p><span className="text-slate-400">Type:</span> {detail.approvable_type?.replace('_', ' ')}</p>
              <p><span className="text-slate-400">Amount:</span> {detail.amount ? `TZS ${Number(detail.amount).toLocaleString()}` : '—'}</p>
              <p><span className="text-slate-400">Status:</span> <span className="capitalize">{detail.status}</span></p>
              <p><span className="text-slate-400">Submitted by:</span> {detail.submitter?.name || '—'}</p>
              <p><span className="text-slate-400">Workflow:</span> {detail.workflow?.name}</p>
            </div>

            <div>
              <h3 className="text-xs font-bold text-slate-500 uppercase mb-2">Action History</h3>
              {detail.actions?.length > 0 ? (
                <div className="space-y-2">
                  {detail.actions.map((action) => (
                    <div key={action.id} className="border border-slate-200 rounded-lg p-3 text-sm">
                      <div className="flex justify-between items-center mb-1">
                        <span className="font-semibold capitalize text-slate-700">{action.action}</span>
                        <span className="text-xs text-slate-400">
                          {new Date(action.created_at).toLocaleString()}
                        </span>
                      </div>
                      <p className="text-slate-500 text-xs mb-1">by {action.user?.name || '—'}</p>
                      {action.comment && <p className="text-slate-600 text-sm">{action.comment}</p>}
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-slate-400 text-sm">No actions recorded yet.</p>
              )}
            </div>
          </div>
        )}

        <div className="flex justify-end pt-4">
          <button
            onClick={onClose}
            className="text-sm font-medium text-slate-600 px-4 py-2 rounded-lg hover:bg-slate-100"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  )
}

export default ApprovalDetailModal