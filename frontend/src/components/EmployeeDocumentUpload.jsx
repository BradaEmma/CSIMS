import { useEffect, useState } from 'react'
import { Upload, FileText, Trash2, AlertTriangle } from 'lucide-react'

const API_BASE_URL = 'http://127.0.0.1:8000/api/v1'
const STORAGE_URL = API_BASE_URL.replace('/api/v1', '') + '/storage'

function authHeaders() {
  const token = localStorage.getItem('csims_token')
  return { Authorization: `Bearer ${token}`, Accept: 'application/json' }
}

function EmployeeDocumentUpload({
  employeeId,
  docType,
  label,
  single = false,
  requiredLabel = null,
  onChanged = () => {},
}) {
  const [documents, setDocuments] = useState([])
  const [uploading, setUploading] = useState(false)
  const [error, setError] = useState('')

  function loadDocuments() {
    if (!employeeId) return

    fetch(`${API_BASE_URL}/employees/${employeeId}/documents?type=${encodeURIComponent(docType)}`, {
      headers: authHeaders(),
    })
      .then((res) => res.json())
      .then((res) => setDocuments(res.data || []))
      .catch(() => {})
  }

  useEffect(() => {
    loadDocuments()
  }, [employeeId])

  async function uploadFile(file) {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('type', docType)

    const res = await fetch(`${API_BASE_URL}/employees/${employeeId}/documents`, {
      method: 'POST',
      headers: authHeaders(),
      body: formData,
    })

    const data = await res.json()

    if (!res.ok) {
      throw new Error(data.message || 'Upload failed')
    }
  }

  async function deleteFile(id) {
    const res = await fetch(`${API_BASE_URL}/employees/${employeeId}/documents/${id}`, {
      method: 'DELETE',
      headers: authHeaders(),
    })

    if (!res.ok) {
      throw new Error('Delete failed')
    }
  }

  async function handleFileChange(e) {
    const file = e.target.files[0]
    if (!file) return

    setError('')
    setUploading(true)

    try {
      // Single-document types replace the old file
      if (single && documents.length > 0) {
        await deleteFile(documents[0].id)
      }

      await uploadFile(file)
      loadDocuments()
      onChanged()
    } catch (err) {
      setError(err.message)
    } finally {
      setUploading(false)
      e.target.value = ''
    }
  }

  async function handleDelete(id) {
    setError('')

    try {
      await deleteFile(id)
      loadDocuments()
      onChanged()
    } catch (err) {
      setError(err.message)
    }
  }

  const visibleDocs = single ? documents.slice(0, 1) : documents
  const hasDoc = visibleDocs.length > 0

  return (
    <div>
      <label className="text-xs font-medium text-slate-500 block mb-1">{label}</label>

      {error && <p className="text-xs text-danger mb-2">{error}</p>}

      {!hasDoc && requiredLabel && (
        <p className="flex items-center gap-1.5 text-xs text-warning bg-warning-bg px-2.5 py-1.5 rounded-lg mb-2">
          <AlertTriangle size={13} />
          {requiredLabel} required
        </p>
      )}

      <div className="space-y-2 mb-2">
        {visibleDocs.map((doc) => (
          <div
            key={doc.id}
            className="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm"
          >
            <a
              href={`${STORAGE_URL}/${doc.file_path}`}
              target="_blank"
              rel="noreferrer"
              className="flex items-center gap-2 text-primary hover:underline truncate min-w-0"
            >
              <FileText size={14} className="flex-shrink-0" />
              <span className="truncate">{doc.original_filename}</span>
            </a>

            <button
              type="button"
              onClick={() => handleDelete(doc.id)}
              className="text-danger hover:text-danger flex-shrink-0 ml-2"
            >
              <Trash2 size={14} />
            </button>
          </div>
        ))}
      </div>

      <label className="flex items-center justify-center gap-2 border border-dashed border-slate-300 rounded-lg py-3 text-sm text-slate-500 hover:bg-slate-50 cursor-pointer transition">
        <Upload size={15} />

        {uploading ? 'Uploading...' : hasDoc && single ? `Replace ${label}` : 'Upload file'}

        <input
          type="file"
          accept=".pdf,.jpg,.jpeg,.png"
          onChange={handleFileChange}
          disabled={uploading}
          className="hidden"
        />
      </label>
    </div>
  )
}

export default EmployeeDocumentUpload