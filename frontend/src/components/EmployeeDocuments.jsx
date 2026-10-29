import { useEffect, useState } from 'react'
import EmployeeDocumentUpload from './EmployeeDocumentUpload'

const API_BASE_URL = 'http://127.0.0.1:8000/api/v1'
const REQUIRED_TYPES = ['passport_photo', 'national_id', 'employment_contract', 'cv']

function authHeaders() {
  const token = localStorage.getItem('csims_token')
  return { Authorization: `Bearer ${token}`, Accept: 'application/json' }
}

function EmployeeDocuments({ employeeId }) {
  const [allDocs, setAllDocs] = useState([])

  function loadAll() {
    if (!employeeId) return
    fetch(`${API_BASE_URL}/employees/${employeeId}/documents`, { headers: authHeaders() })
      .then((res) => res.json())
      .then((res) => setAllDocs(res.data || []))
      .catch(() => {})
  }

  useEffect(() => {
    loadAll()
  }, [employeeId])

  if (!employeeId) {
    return <p className="text-xs text-slate-400 italic">Save the employee first, then manage their documents.</p>
  }

  const presentTypes = new Set(allDocs.map((d) => d.type))
  const isComplete = REQUIRED_TYPES.every((t) => presentTypes.has(t))

  return (
    <div>
      <div className="flex items-center justify-between mb-3">
        <p className="text-xs font-bold text-slate-500 uppercase">Documentation</p>
        <span
          className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ${
            isComplete ? 'bg-success-bg text-success' : 'bg-warning-bg text-warning'
          }`}
        >
          {isComplete ? 'Complete' : 'Incomplete'}
        </span>
      </div>

      <div className="space-y-4">
        <EmployeeDocumentUpload
          employeeId={employeeId}
          docType="national_id"
          label="National ID / NIDA"
          single
          requiredLabel="National ID"
          onChanged={loadAll}
        />
        <EmployeeDocumentUpload
          employeeId={employeeId}
          docType="employment_contract"
          label="Employment Contract"
          single
          requiredLabel="Employment contract"
          onChanged={loadAll}
        />
        <EmployeeDocumentUpload
          employeeId={employeeId}
          docType="cv"
          label="CV / Resume"
          single
          requiredLabel="CV"
          onChanged={loadAll}
        />
        <EmployeeDocumentUpload
          employeeId={employeeId}
          docType="other"
          label="Other Supporting Documents"
          onChanged={loadAll}
        />
      </div>
      <p className="text-[11px] text-slate-400 mt-3">The passport photo is the circle at the top of this window.</p>
    </div>
  )
}

export default EmployeeDocuments