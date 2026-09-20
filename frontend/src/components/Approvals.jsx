import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import ApprovalActionModal from './ApprovalActionModal'
import ApprovalDetailModal from './ApprovalDetailModal'
import { apiGet, apiPost } from '../lib/api'

function ApprovalStatusBadge({ status }) {
  const styles = {
    pending: 'bg-warning-bg text-warning',
    approved: 'bg-success-bg text-success',
    rejected: 'bg-danger-bg text-danger',
    returned: 'bg-danger-bg text-danger',
    cancelled: 'bg-slate-100 text-slate-500',
  }
  return (
    <span className={`text-xs font-semibold px-2.5 py-1 rounded-full capitalize ${styles[status] || 'bg-slate-100 text-slate-600'}`}>
      {status}
    </span>
  )
}

function Approvals() {
  const roles = JSON.parse(localStorage.getItem('csims_roles') || '[]')
  const canSubmit = roles.includes('admin') || roles.includes('manager')

  const [requests, setRequests] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [actionState, setActionState] = useState(null) // { request, action }
  const [detailId, setDetailId] = useState(null)
  const [myRequests, setMyRequests] = useState([])

  const [showForm, setShowForm] = useState(false)
  const [departments, setDepartments] = useState([])
  const [sites, setSites] = useState([])
  const [form, setForm] = useState({ department_id: '', amount: '', reason: '', site_id: '' })
  const [submitting, setSubmitting] = useState(false)
  const [submitError, setSubmitError] = useState('')
  const [submitMessage, setSubmitMessage] = useState('')

  function loadPending() {
    setLoading(true)
    apiGet('/approvals/pending')
      .then((res) => setRequests(res.data || []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadPending()
    loadMine()
  }, [])

  function handleSaved() {
    setActionState(null)
    loadPending()
    loadMine()
  }

  function loadMine() {
    apiGet('/approvals/mine')
      .then((res) => setMyRequests(res.data || []))
      .catch(() => {})
  }

  function openForm() {
    setSubmitError('')
    setSubmitMessage('')
    if (departments.length === 0) {
      apiGet('/departments').then((res) => setDepartments(Array.isArray(res) ? res : []))
    }
    if (sites.length === 0) {
      apiGet('/sites').then((res) => setSites(Array.isArray(res) ? res : []))
    }
    setShowForm(true)
  }

  async function handleSubmitRequest(e) {
    e.preventDefault()
    setSubmitError('')
    setSubmitMessage('')
    setSubmitting(true)
    try {
      await apiPost('/money-requests', {
        department_id: Number(form.department_id),
        amount: Number(form.amount),
        reason: form.reason,
        site_id: form.site_id ? Number(form.site_id) : null,
      })
      setSubmitMessage('Money request submitted for approval.')
      setForm({ department_id: '', amount: '', reason: '', site_id: '' })
      loadMine()
    } catch (err) {
      setSubmitError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <AppLayout title="Approvals" subtitle="Requests awaiting your action">
      {loading && <p className="text-slate-500">Loading...</p>}

      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

            {canSubmit && (
        <div className="bg-white rounded-xl border border-slate-200 p-5 mb-6">
          <div className="flex items-center justify-between mb-2">
            <h2 className="text-sm font-bold text-slate-800">Submit a Money Request</h2>
            {!showForm && (
              <button
                onClick={openForm}
                className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition"
              >
                + New Request
              </button>
            )}
          </div>

          {submitMessage && (
            <p className="text-sm text-success bg-success-bg px-3 py-2 rounded-lg mt-3">{submitMessage}</p>
          )}

          {showForm && (
            <form onSubmit={handleSubmitRequest} className="space-y-3 mt-3">
              {submitError && (
                <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg">{submitError}</p>
              )}
              <div>
                <label className="text-xs font-medium text-slate-500 block mb-1">Department</label>
                <select
                  value={form.department_id}
                  onChange={(e) => setForm((p) => ({ ...p, department_id: e.target.value }))}
                  required
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                >
                  <option value="">Select department</option>
                  {departments.map((d) => (
                    <option key={d.id} value={d.id}>{d.name}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="text-xs font-medium text-slate-500 block mb-1">Amount (TZS)</label>
                <input
                  type="number"
                  min="0.01"
                  step="any"
                  value={form.amount}
                  onChange={(e) => setForm((p) => ({ ...p, amount: e.target.value }))}
                  required
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                />
              </div>
              <div>
                <label className="text-xs font-medium text-slate-500 block mb-1">Reason</label>
                <textarea
                  value={form.reason}
                  onChange={(e) => setForm((p) => ({ ...p, reason: e.target.value }))}
                  required
                  rows={2}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                />
              </div>
              <div>
                <label className="text-xs font-medium text-slate-500 block mb-1">Site (optional)</label>
                <select
                  value={form.site_id}
                  onChange={(e) => setForm((p) => ({ ...p, site_id: e.target.value }))}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                >
                  <option value="">— None —</option>
                  {sites.map((s) => (
                    <option key={s.id} value={s.id}>{s.name}</option>
                  ))}
                </select>
              </div>
              <div className="flex gap-3">
                <button
                  type="submit"
                  disabled={submitting}
                  className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition disabled:opacity-50"
                >
                  {submitting ? 'Submitting...' : 'Submit Request'}
                </button>
                <button
                  type="button"
                  onClick={() => setShowForm(false)}
                  className="text-sm font-medium text-slate-600 px-4 py-2 rounded-lg hover:bg-slate-100"
                >
                  Cancel
                </button>
              </div>
            </form>
          )}
        </div>
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
                    {request.approvable_summary && (
                      <span className="block text-xs font-normal text-slate-400 normal-case">
                        {request.approvable_summary}
                      </span>
                    )}
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

      <div className="bg-white rounded-xl border border-slate-200 p-5 mt-6">
        <h2 className="text-sm font-bold text-slate-800 mb-4">
          My Requests ({myRequests.length})
        </h2>

        <table className="w-full text-sm">
          <thead>
            <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
              <th className="pb-2">Submitted</th>
              <th className="pb-2">Type</th>
              <th className="pb-2">Amount</th>
              <th className="pb-2">Status</th>
              <th className="pb-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            {myRequests.map((request) => (
              <tr key={request.id} className="border-b border-slate-100 last:border-0">
                <td className="py-2.5 text-slate-600">
                  {new Date(request.created_at).toLocaleDateString()}
                </td>
                <td className="py-2.5 font-medium text-slate-700 capitalize">
                  {request.approvable_type?.replace('_', ' ')}
                  {request.approvable_summary && (
                    <span className="block text-xs font-normal text-slate-400 normal-case">
                      {request.approvable_summary}
                    </span>
                  )}
                </td>
                <td className="py-2.5 text-slate-600">
                  {request.amount ? `TZS ${Number(request.amount).toLocaleString()}` : '—'}
                </td>
                <td className="py-2.5"><ApprovalStatusBadge status={request.status} /></td>
                <td className="py-2.5">
                  <button
                    onClick={() => setDetailId(request.id)}
                    className="text-slate-500 hover:underline text-xs font-medium"
                  >
                    View
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {myRequests.length === 0 && (
          <p className="text-slate-400 text-sm text-center py-8">You haven't submitted any requests yet.</p>
        )}
      </div>

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