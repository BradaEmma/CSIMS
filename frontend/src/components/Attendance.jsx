import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import { apiGet, apiPost } from '../lib/api'

function StatCard({ label, value }) {
  return (
    <div className="bg-white rounded-xl border border-slate-200 p-4">
      <p className="text-xs text-slate-400 uppercase font-medium mb-1">{label}</p>
      <p className="text-2xl font-bold text-slate-800">{value}</p>
    </div>
  )
}

function StatusBadge({ status }) {
  const styles = {
    present: 'bg-success-bg text-success',
    late: 'bg-warning-bg text-warning',
    completed: 'bg-slate-100 text-slate-500',
  }
  return (
    <span className={`text-xs font-semibold px-2.5 py-1 rounded-full capitalize ${styles[status] || 'bg-slate-100 text-slate-600'}`}>
      {status}
    </span>
  )
}

function Attendance() {
  const [summary, setSummary] = useState(null)
  const [guards, setGuards] = useState([])
  const [sites, setSites] = useState([])
  const [records, setRecords] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const [selectedGuardId, setSelectedGuardId] = useState('')
  const [selectedSiteId, setSelectedSiteId] = useState('')
  const [actionMessage, setActionMessage] = useState('')
  const [actionError, setActionError] = useState('')
  const [actionLoading, setActionLoading] = useState(false)

  function loadAll() {
    setLoading(true)
    setError('')
    Promise.all([
      apiGet('/attendance/today'),
      apiGet('/guards'),
      apiGet('/sites'),
    ])
      .then(async ([summaryRes, guardsRes, sitesRes]) => {
        const guardList = Array.isArray(guardsRes) ? guardsRes : []
        const siteList = (Array.isArray(sitesRes) ? sitesRes : []).filter((s) => s.status === 'active')

        setSummary(summaryRes.data)
        setGuards(guardList)
        setSites(siteList)

        // No single "all attendance today" endpoint — combine per-site results
        const perSite = await Promise.all(
          siteList.map((site) =>
            apiGet(`/attendance/site/${site.id}`).then((res) => res.data || [])
          )
        )
        setRecords(perSite.flat())
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadAll()
  }, [])

  async function handleCheckIn(e) {
    e.preventDefault()
    setActionMessage('')
    setActionError('')
    setActionLoading(true)
    try {
      const res = await apiPost('/attendance/check-in', {
        guard_id: Number(selectedGuardId),
        site_id: selectedSiteId ? Number(selectedSiteId) : null,
      })
      setActionMessage(res.message || 'Checked in successfully')
      loadAll()
    } catch (err) {
      setActionError(err.message)
    } finally {
      setActionLoading(false)
    }
  }

  async function handleCheckOut(e) {
    e.preventDefault()
    setActionMessage('')
    setActionError('')
    setActionLoading(true)
    try {
      const res = await apiPost('/attendance/check-out', {
        guard_id: Number(selectedGuardId),
      })
      setActionMessage(res.message || 'Checked out successfully')
      loadAll()
    } catch (err) {
      setActionError(err.message)
    } finally {
      setActionLoading(false)
    }
  }

  return (
    <AppLayout title="Attendance" subtitle="Today's check-ins and site coverage">
      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

      {loading ? (
        <p className="text-slate-500">Loading...</p>
      ) : (
        <>
          {/* Today's summary stats */}
          <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <StatCard label="Rostered" value={summary?.rostered ?? 0} />
            <StatCard label="Checked In" value={summary?.checked_in ?? 0} />
            <StatCard label="Late" value={summary?.late ?? 0} />
            <StatCard label="Checked Out" value={summary?.checked_out ?? 0} />
            <StatCard label="Absent" value={summary?.absent ?? 0} />
          </div>

          {/* Manual check-in / check-out panel */}
          <div className="bg-white rounded-xl border border-slate-200 p-5 mb-6">
            <h2 className="text-sm font-bold text-slate-800 mb-4">Check In / Check Out</h2>

            {actionMessage && (
              <p className="text-sm text-success bg-success-bg px-3 py-2 rounded-lg mb-4">{actionMessage}</p>
            )}
            {actionError && (
              <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mb-4">{actionError}</p>
            )}

            <div className="flex flex-wrap items-end gap-3">
              <div>
                <label className="text-xs font-medium text-slate-500 block mb-1">Guard</label>
                <select
                  value={selectedGuardId}
                  onChange={(e) => setSelectedGuardId(e.target.value)}
                  className="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary min-w-[180px]"
                >
                  <option value="">Select guard</option>
                  {guards.map((g) => (
                    <option key={g.id} value={g.id}>{g.name}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="text-xs font-medium text-slate-500 block mb-1">Site (check-in only, optional)</label>
                <select
                  value={selectedSiteId}
                  onChange={(e) => setSelectedSiteId(e.target.value)}
                  className="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary min-w-[180px]"
                >
                  <option value="">Auto-resolve from roster</option>
                  {sites.map((s) => (
                    <option key={s.id} value={s.id}>{s.name}</option>
                  ))}
                </select>
              </div>

              <button
                onClick={handleCheckIn}
                disabled={!selectedGuardId || actionLoading}
                className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition disabled:opacity-50"
              >
                {actionLoading ? 'Working...' : 'Check In'}
              </button>

              <button
                onClick={handleCheckOut}
                disabled={!selectedGuardId || actionLoading}
                className="border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg hover:bg-slate-50 transition disabled:opacity-50"
              >
                {actionLoading ? 'Working...' : 'Check Out'}
              </button>
            </div>
          </div>

          {/* Today's records across all active sites */}
          <div className="bg-white rounded-xl border border-slate-200 p-5">
            <h2 className="text-sm font-bold text-slate-800 mb-4">
              Today's Attendance ({records.length})
            </h2>

            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                  <th className="pb-2">Guard</th>
                  <th className="pb-2">Site</th>
                  <th className="pb-2">Shift</th>
                  <th className="pb-2">Check In</th>
                  <th className="pb-2">Check Out</th>
                  <th className="pb-2">Hours</th>
                  <th className="pb-2">Status</th>
                </tr>
              </thead>
              <tbody>
                {records.map((r) => (
                  <tr key={r.id} className="border-b border-slate-100 last:border-0">
                    <td className="py-2.5 font-medium text-slate-700">{r.security_guard?.name || '—'}</td>
                    <td className="py-2.5 text-slate-600">{r.site?.name || '—'}</td>
                    <td className="py-2.5 text-slate-600 capitalize">{r.roster_assignment?.shift || '—'}</td>
                    <td className="py-2.5 text-slate-600">
                      {r.check_in_at ? new Date(r.check_in_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—'}
                    </td>
                    <td className="py-2.5 text-slate-600">
                      {r.check_out_at ? new Date(r.check_out_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—'}
                    </td>
                    <td className="py-2.5 text-slate-600">{r.hours_worked ?? '—'}</td>
                    <td className="py-2.5"><StatusBadge status={r.status} /></td>
                  </tr>
                ))}
              </tbody>
            </table>

            {records.length === 0 && (
              <p className="text-slate-400 text-sm text-center py-8">No attendance recorded today.</p>
            )}
          </div>
        </>
      )}
    </AppLayout>
  )
}

export default Attendance