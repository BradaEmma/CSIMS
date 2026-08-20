import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import IncidentModal from './IncidentModal'
import ResolveModal from './ResolveModal'
import { apiGet } from '../lib/api'

function SeverityBadge({ severity }) {
  const styles = {
    low: 'bg-info-bg text-info',
    medium: 'bg-warning-bg text-warning',
    high: 'bg-danger-bg text-danger',
    critical: 'bg-danger text-white',
  }
  return (
    <span className={`text-xs font-semibold px-2.5 py-1 rounded-full capitalize ${styles[severity] || 'bg-slate-100 text-slate-600'}`}>
      {severity}
    </span>
  )
}

function StatusBadge({ status }) {
  const styles = {
    open: 'bg-danger-bg text-danger',
    under_review: 'bg-warning-bg text-warning',
    resolved: 'bg-success-bg text-success',
    closed: 'bg-slate-100 text-slate-500',
  }
  const labels = {
    open: 'Open',
    under_review: 'Under Review',
    resolved: 'Resolved',
    closed: 'Closed',
  }
  return (
    <span className={`text-xs font-semibold px-2.5 py-1 rounded-full ${styles[status] || 'bg-slate-100 text-slate-600'}`}>
      {labels[status] || status}
    </span>
  )
}

function Incidents() {
  const [incidents, setIncidents] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [modalOpen, setModalOpen] = useState(false)
  const [resolvingIncident, setResolvingIncident] = useState(null)

  function loadIncidents() {
    setLoading(true)
    apiGet('/incidents')
      .then((res) => setIncidents(res.data?.data || []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadIncidents()
  }, [])

  function handleSaved() {
    setModalOpen(false)
    setResolvingIncident(null)
    loadIncidents()
  }

  return (
    <AppLayout title="Incidents" subtitle="Track and manage security incidents across all sites">
      {loading && <p className="text-slate-500">Loading...</p>}

      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

      {!loading && (
        <div className="bg-white rounded-xl border border-slate-200 p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-sm font-bold text-slate-800">
              All Incidents ({incidents.length})
            </h2>
            <button
              onClick={() => setModalOpen(true)}
              className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition"
            >
              + Report Incident
            </button>
          </div>

          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                <th className="pb-2">Date</th>
                <th className="pb-2">Site</th>
                <th className="pb-2">Type</th>
                <th className="pb-2">Severity</th>
                <th className="pb-2">Status</th>
                <th className="pb-2">Reported By</th>
                <th className="pb-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {incidents.map((incident) => (
                <tr key={incident.id} className="border-b border-slate-100 last:border-0">
                  <td className="py-2.5 text-slate-600">
                    {new Date(incident.occurred_at).toLocaleDateString()}
                  </td>
                  <td className="py-2.5 font-medium text-slate-700">{incident.site?.name || '—'}</td>
                  <td className="py-2.5 text-slate-600">{incident.incident_type?.name || '—'}</td>
                  <td className="py-2.5"><SeverityBadge severity={incident.severity} /></td>
                  <td className="py-2.5"><StatusBadge status={incident.status} /></td>
                  <td className="py-2.5 text-slate-600">{incident.reported_by?.name || '—'}</td>
                  <td className="py-2.5">
                    {incident.status !== 'resolved' && incident.status !== 'closed' && (
                      <button
                        onClick={() => setResolvingIncident(incident)}
                        className="text-success hover:underline text-xs font-medium"
                      >
                        Resolve
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {incidents.length === 0 && (
            <p className="text-slate-400 text-sm text-center py-8">No incidents found.</p>
          )}
        </div>
      )}

      {modalOpen && (
        <IncidentModal onClose={() => setModalOpen(false)} onSaved={handleSaved} />
      )}

      {resolvingIncident && (
        <ResolveModal
          incident={resolvingIncident}
          onClose={() => setResolvingIncident(null)}
          onSaved={handleSaved}
        />
      )}
    </AppLayout>
  )
}

export default Incidents