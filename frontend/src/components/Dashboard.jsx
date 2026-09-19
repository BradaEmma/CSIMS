import { useEffect, useState } from 'react'
import { Building2, Users, CalendarCheck, ShieldCheck, UserX, AlertTriangle, IdCard, Landmark, Wallet } from 'lucide-react'
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

function currentPeriod() {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
}

function formatCurrency(value) {
  return `TZS ${Number(value).toLocaleString()}`
}

// ─────────────────────────────────────────────────────────────
// admin / supervisor / manager — original operations dashboard,
// unchanged in content and logic, just extracted into its own
// component so Dashboard() can branch by role.
// ─────────────────────────────────────────────────────────────
function OperationsDashboard() {
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

  if (loading) return <p className="text-slate-500">Loading...</p>
  if (error) return <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg">{error}</p>

  return (
    <div>
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
  )
}

// ─────────────────────────────────────────────────────────────
// hr — total employees + breakdown by department, built entirely
// from /employees and /departments, which hr already has view
// access to. No new backend surface.
// ─────────────────────────────────────────────────────────────
function HrDashboard() {
  const [employees, setEmployees] = useState(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    apiGet('/employees')
      .then((res) => setEmployees(Array.isArray(res) ? res : []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <p className="text-slate-500">Loading...</p>
  if (error) return <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg">{error}</p>

  const byDepartment = employees.reduce((acc, emp) => {
    const name = emp.department?.name || 'Unassigned'
    acc[name] = (acc[name] || 0) + 1
    return acc
  }, {})

  return (
    <div>
      <div className="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
        <StatCard label="Total Employees" value={employees.length} icon={IdCard} />
        <StatCard label="Departments Represented" value={Object.keys(byDepartment).length} icon={Landmark} />
      </div>

      <div className="bg-white rounded-xl border border-slate-200 p-5">
        <h2 className="text-sm font-bold text-slate-800 mb-4">Employees by Department</h2>
        <table className="w-full text-sm">
          <thead>
            <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
              <th className="pb-2">Department</th>
              <th className="pb-2">Employees</th>
            </tr>
          </thead>
          <tbody>
            {Object.entries(byDepartment).map(([name, count]) => (
              <tr key={name} className="border-b border-slate-100 last:border-0">
                <td className="py-2 font-medium text-slate-700">{name}</td>
                <td className="py-2 text-slate-600">{count}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}

// ─────────────────────────────────────────────────────────────
// accountant — current period payroll totals + status counts,
// built entirely from /payroll, which accountant already has
// view access to. No new backend surface.
// ─────────────────────────────────────────────────────────────
function FinanceDashboard() {
  const [records, setRecords] = useState(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const period = currentPeriod()

  useEffect(() => {
    apiGet(`/payroll?period=${period}`)
      .then((res) => setRecords(res.data?.data || []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }, [period])

  if (loading) return <p className="text-slate-500">Loading...</p>
  if (error) return <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg">{error}</p>

  const totals = records.reduce(
    (acc, r) => ({
      gross: acc.gross + Number(r.gross_pay) + Number(r.overtime_pay),
      net: acc.net + Number(r.net_pay),
      draft: acc.draft + (r.status === 'draft' ? 1 : 0),
      finalized: acc.finalized + (r.status === 'finalized' ? 1 : 0),
      paid: acc.paid + (r.status === 'paid' ? 1 : 0),
    }),
    { gross: 0, net: 0, draft: 0, finalized: 0, paid: 0 }
  )

  return (
    <div>
      <p className="text-xs text-slate-500 mb-4">Period: {period}</p>
      <div className="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
        <StatCard label="Total Gross Pay" value={formatCurrency(totals.gross)} icon={Wallet} />
        <StatCard label="Total Net Pay" value={formatCurrency(totals.net)} tone="success" icon={Wallet} />
        <StatCard label="Records This Period" value={records.length} />
      </div>

      <div className="grid grid-cols-3 gap-4">
        <StatCard label="Draft" value={totals.draft} />
        <StatCard label="Finalized" value={totals.finalized} tone="warning" />
        <StatCard label="Paid" value={totals.paid} tone="success" />
      </div>
    </div>
  )
}

function Dashboard() {
  const roles = JSON.parse(localStorage.getItem('csims_roles') || '[]')
  const user = JSON.parse(localStorage.getItem('csims_user') || '{}')

  let content
  if (roles.includes('admin') || roles.includes('supervisor') || roles.includes('manager')) {
    content = <OperationsDashboard />
  } else if (roles.includes('hr')) {
    content = <HrDashboard />
  } else if (roles.includes('accountant')) {
    content = <FinanceDashboard />
  } else {
    content = <p className="text-slate-500">No dashboard content is available for your role yet.</p>
  }

  return (
    <AppLayout
      title={`Welcome back, ${user.name || ''}`}
      subtitle="Here's what's happening across CSIMS today."
    >
      {content}
    </AppLayout>
  )
}

export default Dashboard