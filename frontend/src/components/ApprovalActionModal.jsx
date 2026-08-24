import { useState } from 'react'
import { apiPost } from '../lib/api'

const actionConfig = {
  approve: {
    title: 'Approve Request',
    endpoint: 'approve',
    commentRequired: false,
    buttonLabel: 'Approve',
    buttonClass: 'bg-success text-white hover:opacity-90',
    loadingLabel: 'Approving...',
  },
  reject: {
    title: 'Reject Request',
    endpoint: 'reject',
    commentRequired: true,
    buttonLabel: 'Reject',
    buttonClass: 'bg-danger text-white hover:opacity-90',
    loadingLabel: 'Rejecting...',
  },
  return: {
    title: 'Return Request',
    endpoint: 'return',
    commentRequired: true,
    buttonLabel: 'Return',
    buttonClass: 'bg-warning text-white hover:opacity-90',
    loadingLabel: 'Returning...',
  },
}

function ApprovalActionModal({ request, action, onClose, onSaved }) {
  const [comment, setComment] = useState('')
  const [error, setError] = useState('')
  const [saving, setSaving] = useState(false)

  const config = actionConfig[action]

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')
    setSaving(true)

    try {
      await apiPost(`/approvals/${request.id}/${config.endpoint}`, { comment: comment || null })
      onSaved()
    } catch (err) {
      setError(err.message)
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
      <div className="bg-white rounded-xl w-full max-w-md p-6">
        <h2 className="text-lg font-bold text-slate-800 mb-1">{config.title}</h2>
        <p className="text-sm text-slate-500 mb-4">
          {request.approvable_type?.replace('_', ' ')} — {request.amount ? `TZS ${Number(request.amount).toLocaleString()}` : 'No amount'}
        </p>

        {error && (
          <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mb-4">{error}</p>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">
              Comment {config.commentRequired ? '' : '(optional)'}
            </label>
            <textarea
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              rows={4}
              required={config.commentRequired}
              placeholder="Add a comment..."
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            />
          </div>

          <div className="flex justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={onClose}
              className="text-sm font-medium text-slate-600 px-4 py-2 rounded-lg hover:bg-slate-100"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={saving}
              className={`text-sm font-medium px-4 py-2 rounded-lg transition disabled:opacity-50 ${config.buttonClass}`}
            >
              {saving ? config.loadingLabel : config.buttonLabel}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default ApprovalActionModal