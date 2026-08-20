import { useEffect, useState } from 'react'
import { Building2, Users, CalendarCheck, ShieldCheck, UserX, AlertTriangle } from 'lucide-react'
import AppLayout from './AppLayout'
import { apiGet } from '../lib/api'

function StatCard({ label, value, sublabel, tone, icon: Icon }) {
  const toneClass = {
    success: 'text-success',
    warning: 'text-warning',
    danger: 'text-danger',
    default: 'text-slate-900',
  }[tone || 'default']

  const iconBg = {
    success: 'bg-success-bg text-success',
    warning: 'bg-warning-bg text-warning',
    danger: 'bg-danger-bg text-danger',
    default: 'bg-slate-100 text-slate-500',
  }[tone || 'default']

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-5 flex items-start justify-between">
      <div>
        <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{label}</p>
        <p className={`text-2xl font-bold mt-1 ${toneClass}`}>{value}</p>
        {sublabel && <p className="text-xs text-slate-400 mt-1">{sublabel}</p>}
      </div>
      {Icon && (
        <div className={`w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 ${iconBg}`}>
          <Icon size={18} />
        </div>
      )}
    </div>
  )
}

function StatusBadge({ status }) {
  const styles = {
    'On Track': 'bg-success-bg text-success',
    'Shortage': 'bg-warning-bg text-warning',
  }
  return (
    <span className={`text-xs font-semibold px-2.5 py-1 rounded-full ${styles[status] || 'bg-slate-100 text-slate-600'}`}>
      {status}
    </span>
  )
}

function Dashboard() {
  const [adminSummary, setAdminSummary] = useState(null)
  const [liveShift, setLiveShift] = useState(null)
  const [incidentSummary, setIncidentSummary] = useState(null)
  const [sitesCount, setSitesCount] = useState(null)
  const [guardsCount, setGuardsCount] = useState(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    Promise.all([
      apiGet('/dashboard/admin'),
      apiGet('/dashboard/live-shift'),
      apiGet('/incidents/summary'),
      apiGet('/sites'),
      apiGet('/guards'),
    ])
      .then(([admin, live, incidents, sites, guards]) => {
        setAdminSummary(admin)
        setLiveShift(live)
        setIncidentSummary(incidents.data)
        // SiteController/GuardController return raw arrays, not { data: [...] }
        setSitesCount(Array.isArray(sites) ? sites.length : 0)
        setGuardsCount(Array.isArray(guards) ? guards.length : 0)
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }, [])

  const todaysPosts = liveShift
    ? liveShift.site_summary.reduce((sum, s) => sum + s.required_guards, 0)
    : 0

  const onDutyNow = liveShift
    ? liveShift.site_summary.reduce((sum, s) => sum + s.present_guards, 0)
    : 0

  return (
    <AppLayout
      title={`Welcome back, ${JSON.parse(localStorage.getItem('csims_user') || '{}').name || ''}`}
      subtitle="Here's what's happening across CSIMS today."
    >
      {loading && <p className="text-slate-500">Loading...</p>}

      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg">{error}</p>
      )}

      {!loading && !error && (
        <div>
          {/* Stats row */}
          <div className="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-7 gap-4">
            <StatCard label="Total Sites" value={sitesCount} icon={Building2} />
            <StatCard label="Total Guards" value={guardsCount} icon={Users} />
            <StatCard label="Today's Posts" value={todaysPosts} icon={CalendarCheck} />
            <StatCard label="On Duty Now" value={onDutyNow} tone="success" icon={ShieldCheck} />
            <StatCard
              label="No-Shows Today"
              value={adminSummary.company_summary.absent}
              sublabel="Rostered guards who didn't check in"
              tone={adminSummary.company_summary.absent > 0 ? 'danger' : 'default'}
              icon={UserX}
            />
            <StatCard
              label="Unfilled Positions"
              value={liveShift.site_summary.reduce((sum, s) => sum + s.missing_guards, 0)}
              sublabel="Includes sites with no eligible guards"
              tone={liveShift.site_summary.reduce((sum, s) => sum + s.missing_guards, 0) > 0 ? 'warning' : 'default'}
              icon={AlertTriangle}
            />
            <StatCard
              label="Incidents (Month)"
              value={incidentSummary.month_count}
              tone={incidentSummary.month_count > 0 ? 'warning' : 'default'}
              icon={AlertTriangle}
            />
          </div>

          {/* Live Shift Overview */}
          <div className="mt-6 bg-white rounded-xl border border-slate-200 p-5">
            <h2 className="text-sm font-bold text-slate-800 mb-4">
              Live Shift Overview — {liveShift.shift === 'morning' ? 'Day' : 'Night'} Shift
            </h2>
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                  <th className="pb-2">Site</th>
                  <th className="pb-2">Required</th>
                  <th className="pb-2">On Duty</th>
                  <th className="pb-2">Missing</th>
                  <th className="pb-2">Status</th>
                </tr>
              </thead>
              <tbody>
                {liveShift.site_summary.map((site) => (
                  <tr key={site.site_id} className="border-b border-slate-100 last:border-0">
                    <td className="py-2 font-medium text-slate-700">{site.site_name}</td>
                    <td className="py-2 text-slate-600">{site.required_guards}</td>
                    <td className="py-2 text-slate-600">{site.present_guards}</td>
                    <td className={`py-2 font-medium ${site.missing_guards > 0 ? 'text-danger' : 'text-slate-600'}`}>
                      {site.missing_guards}
                    </td>
                    <td className="py-2">
                      <StatusBadge status={site.missing_guards > 0 ? 'Shortage' : 'On Track'} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </AppLayout>
  )
}

export default Dashboard