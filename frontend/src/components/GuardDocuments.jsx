import { useEffect, useState } from 'react'
import DocumentUpload from './DocumentUpload'

const API_BASE_URL = 'http://127.0.0.1:8000/api/v1'
const REQUIRED_TYPES = ['passport_photo', 'national_id', 'employment_contract', 'training_certificate']

function authHeaders() {
  const token = localStorage.getItem('csims_token')
  return { 'Authorization': `Bearer ${token}` }
}

function GuardDocuments({ guardId }) {
  const [allDocs, setAllDocs] = useState([])

  function loadAll() {
    if (!guardId) return
    fetch(`${API_BASE_URL}/documents?documentable_type=guard&documentable_id=${guardId}`, {
      headers: authHeaders(),
    })
      .then((res) => res.json())
      .then((res) => setAllDocs(res.data || []))
      .catch(() => {})
  }

  useEffect(() => {
    loadAll()
  }, [guardId])

  if (!guardId) {
    return <p className="text-xs text-slate-400 italic">Save the guard first, then manage their documents.</p>
  }

  const presentTypes = new Set(allDocs.map((d) => d.type))
  const isComplete = REQUIRED_TYPES.every((t) => presentTypes.has(t))

  return (
    <div>
      <div className="flex items-center justify-between mb-3">
        <p className="text-xs font-bold text-slate-500 uppercase">Documentation</p>
        <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ${
          isComplete ? 'bg-success-bg text-success' : 'bg-warning-bg text-warning'
        }`}>
          {isComplete ? 'Complete' : 'Incomplete'}
        </span>
      </div>

      <div className="space-y-4">
        <DocumentUpload
          documentableType="guard"
          documentableId={guardId}
          docType="national_id"
          label="National ID / NIDA"
          single
          requiredLabel="National ID"
        />
        <DocumentUpload
          documentableType="guard"
          documentableId={guardId}
          docType="employment_contract"
          label="Employment / Guard Contract"
          single
          requiredLabel="Employment contract"
        />
        <DocumentUpload
          documentableType="guard"
          documentableId={guardId}
          docType="training_certificate"
          label="Training / Security Certificate"
          single
          requiredLabel="Training certificate"
        />
        <DocumentUpload
          documentableType="guard"
          documentableId={guardId}
          docType="other"
          label="Other Supporting Documents"
        />
      </div>
    </div>
  )
}

export default GuardDocuments 