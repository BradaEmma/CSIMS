import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import { apiGet, apiPost, apiPatch } from '../lib/api'
import { Wallet, RefreshCw } from 'lucide-react'

function StatusBadge({ status }) {
  const styles = {
    draft: 'bg-slate-100 text-slate-500',
    finalized: 'bg-warning-bg text-warning',
    paid: 'bg-success-bg text-success',
  }
  return (
    <span className={`text-xs font-semibold px-2.5 py-1 rounded-full capitalize ${styles[status] || 'bg-slate-100 text-slate-600'}`}>
      {status}
    </span>
  )
}

function currentPeriod() {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
}

function formatCurrency(value) {
  return `TZS ${Number(value).toLocaleString()}`
}

function Payroll() {
  const [records, setRecords] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [period, setPeriod] = useState(currentPeriod())
  const [generating, setGenerating] = useState(false)
  const [message, setMessage] = useState('')
  const [updatingId, setUpdatingId] = useState(null)

  function loadRecords() {
    setLoading(true)
    apiGet(`/payroll?period=${period}`)
      .then((res) => setRecords(res.data?.data || []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadRecords()
  }, [period])

  async function handleGenerateBulk() {
    setGenerating(true)
    setMessage('')
    setError('')
    try {
      const res = await apiPost('/payroll/generate-bulk', { period })
      setMessage(
        `Generated for ${res.data.total_guards} guard(s): ${res.data.succeeded} succeeded, ${res.data.failed} failed.`
      )
      loadRecords()
    } catch (err) {
      setError(err.message)
    } finally {
      setGenerating(false)
    }
  }

  async function handleStatusChange(record, newStatus) {
    setUpdatingId(record.id)
    setError('')
    setMessage('')
    try {
      const res = await apiPatch(`/payroll/${record.id}/status`, { status: newStatus })
      if (res.message) {
        setMessage(res.message)
      }
      loadRecords()
    } catch (err) {
      setError(err.message)
    } finally {
      setUpdatingId(null)
    }
  }

  const totals = records.reduce(
    (acc, r) => ({
      gross: acc.gross + Number(r.gross_pay) + Number(r.overtime_pay),
      net: acc.net + Number(r.net_pay),
    }),
    { gross: 0, net: 0 }
  )

  return (
    <AppLayout title="Payroll" subtitle="Generate and manage guard payroll by period">
      <div className="bg-white rounded-xl border border-slate-200 p-5 mb-6">
        <div className="flex items-center justify-between flex-wrap gap-3">
          <div className="flex items-center gap-3">
            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">Period</label>
              <input
                type="month"
                value={period}
                onChange={(e) => setPeriod(e.target.value)}
                className="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              />
            </div>
          </div>

          <button
            onClick={handleGenerateBulk}
            disabled={generating}
            className="flex items-center gap-2 bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition disabled:opacity-50"
          >
            <RefreshCw size={15} className={generating ? 'animate-spin' : ''} />
            {generating ? 'Generating...' : 'Generate Payroll for Period'}
          </button>
        </div>

        {message && (
          <p className="text-sm text-success bg-success-bg px-3 py-2 rounded-lg mt-4">{message}</p>
        )}
        {error && (
          <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mt-4">{error}</p>
        )}
      </div>

      {/* Summary cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div className="bg-white rounded-xl border border-slate-200 p-5 flex items-center justify-between">
          <div>
            <p className="text-xs text-slate-500 uppercase font-medium">Guards This Period</p>
            <p className="text-2xl font-bold text-slate-900 mt-1">{records.length}</p>
          </div>
          <div className="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center">
            <Wallet size={18} />
          </div>
        </div>
        <div className="bg-white rounded-xl border border-slate-200 p-5">
          <p className="text-xs text-slate-500 uppercase font-medium">Total Gross Pay</p>
          <p className="text-2xl font-bold text-slate-900 mt-1">{formatCurrency(totals.gross)}</p>
        </div>
        <div className="bg-white rounded-xl border border-slate-200 p-5">
          <p className="text-xs text-slate-500 uppercase font-medium">Total Net Pay</p>
          <p className="text-2xl font-bold text-success mt-1">{formatCurrency(totals.net)}</p>
        </div>
      </div>

      {/* Records table */}
      <div className="bg-white rounded-xl border border-slate-200 p-5">
        <h2 className="text-sm font-bold text-slate-800 mb-4">
          Payroll Records — {period} ({records.length})
        </h2>

        {loading ? (
          <p className="text-slate-500">Loading...</p>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                <th className="pb-2">Guard</th>
                <th className="pb-2">Days</th>
                <th className="pb-2">Gross</th>
                <th className="pb-2">Overtime</th>
                <th className="pb-2">NSSF</th>
                <th className="pb-2">PAYE</th>
                <th className="pb-2">Deductions</th>
                <th className="pb-2">Net Pay</th>
                <th className="pb-2">Status</th>
                <th className="pb-2">Action</th>
              </tr>
            </thead>
            <tbody>
              {records.map((r) => (
                <tr key={r.id} className="border-b border-slate-100 last:border-0">
                  <td className="py-2.5 font-medium text-slate-700">{r.security_guard?.name || '—'}</td>
                  <td className="py-2.5 text-slate-600">{r.days_worked}</td>
                  <td className="py-2.5 text-slate-600">{formatCurrency(r.gross_pay)}</td>
                  <td className="py-2.5 text-slate-600">{formatCurrency(r.overtime_pay)}</td>
                  <td className="py-2.5 text-slate-600">{formatCurrency(r.nssf_deduction)}</td>
                  <td className="py-2.5 text-slate-600">{formatCurrency(r.paye_deduction)}</td>
                  <td className="py-2.5 text-slate-600">{formatCurrency(r.other_deductions_total)}</td>
                  <td className="py-2.5 font-semibold text-slate-800">{formatCurrency(r.net_pay)}</td>
                  <td className="py-2.5"><StatusBadge status={r.status} /></td>
                  <td className="py-2.5">
                    {r.status === 'draft' && (
                      <button
                        onClick={() => handleStatusChange(r, 'finalized')}
                        disabled={updatingId === r.id}
                        className="text-warning hover:underline text-xs font-medium disabled:opacity-50"
                      >
                        Finalize
                      </button>
                    )}
                                        {r.status === 'finalized' && r.approval_status === 'pending' && (
                      <span className="text-xs font-medium text-slate-400 italic">
                        Awaiting Approval
                      </span>
                    )}
                    {r.status === 'finalized' && r.approval_status !== 'pending' && (
                      <button
                        onClick={() => handleStatusChange(r, 'paid')}
                        disabled={updatingId === r.id}
                        className="text-success hover:underline text-xs font-medium disabled:opacity-50"
                      >
                        Mark Paid
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}

        {!loading && records.length === 0 && (
          <p className="text-slate-400 text-sm text-center py-8">
            No payroll records for this period yet. Click "Generate Payroll" above to create them.
          </p>
        )}
      </div>
    </AppLayout>
  )
}

export default Payroll