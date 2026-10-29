import { useEffect, useState, useRef } from 'react'
import { Camera, X, Trash2 } from 'lucide-react'

const API_BASE_URL = 'http://127.0.0.1:8000/api/v1'
const STORAGE_URL = API_BASE_URL.replace('/api/v1', '') + '/storage'

function authHeaders() {
  const token = localStorage.getItem('csims_token')
  return { 'Authorization': `Bearer ${token}` }
}

function EmployeePhotoUpload({ employeeId, employeeName }) {
  const [document, setDocument] = useState(null)
  const [uploading, setUploading] = useState(false)
  const [error, setError] = useState('')
  const [lightboxOpen, setLightboxOpen] = useState(false)
  const inputRef = useRef(null)

  function load() {
    if (!employeeId) return
    fetch(`${API_BASE_URL}/employees/${employeeId}/documents?type=passport_photo`, {
      headers: { ...authHeaders(), Accept: 'application/json' },
    })
      .then((res) => res.json())
      .then((res) => setDocument((res.data || [])[0] || null))
      .catch(() => {})
  }

  useEffect(() => {
    load()
  }, [employeeId])

  async function handleFileChange(e) {
    const file = e.target.files[0]
    if (!file) return

    setError('')
    setUploading(true)

    try {
      if (document) {
        await fetch(`${API_BASE_URL}/employees/${employeeId}/documents/${document.id}`, {
          method: 'DELETE',
          headers: { ...authHeaders(), Accept: 'application/json' },
        })
      }

      const formData = new FormData()
      formData.append('file', file)
      formData.append('type', 'passport_photo')

      const res = await fetch(`${API_BASE_URL}/employees/${employeeId}/documents`, {
        method: 'POST',
        headers: { ...authHeaders(), Accept: 'application/json' },
        body: formData,
      })
      const data = await res.json()
      if (!res.ok) throw new Error(data.message || 'Upload failed')

      load()
    } catch (err) {
      setError(err.message)
    } finally {
      setUploading(false)
      e.target.value = ''
    }
  }

  async function handleRemove() {
    if (!document) return
    if (!window.confirm('Remove this photo?')) return

    setError('')
    setUploading(true)

    try {
      const res = await fetch(`${API_BASE_URL}/employees/${employeeId}/documents/${document.id}`, {
        method: 'DELETE',
        headers: { ...authHeaders(), Accept: 'application/json' },
      })
      if (!res.ok) throw new Error('Could not remove the photo')

      setDocument(null)
      setLightboxOpen(false)
    } catch (err) {
      setError(err.message)
    } finally {
      setUploading(false)
    }
  }

  const initials = (employeeName || '?')
    .split(' ')
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()

  const photoUrl = document ? `${STORAGE_URL}/${document.file_path}` : null

  return (
    <div className="flex flex-col items-center">
      <div className="relative">
        <button
          type="button"
          onClick={() => (photoUrl ? setLightboxOpen(true) : inputRef.current?.click())}
          disabled={!employeeId}
          className="w-24 h-24 rounded-full overflow-hidden bg-primary-dark flex items-center justify-center border-2 border-slate-200 disabled:opacity-50"
        >
          {photoUrl ? (
            <img src={photoUrl} alt="" className="w-full h-full object-cover" />
          ) : (
            <span className="text-white text-2xl font-bold">{initials}</span>
          )}
        </button>

        {employeeId && photoUrl && (
          <button
            type="button"
            onClick={handleRemove}
            disabled={uploading}
            className="absolute -bottom-1 -left-1 w-8 h-8 rounded-full bg-white border border-slate-300 flex items-center justify-center shadow-sm hover:bg-slate-50"
            title="Remove photo"
          >
            <Trash2 size={14} className="text-danger" />
          </button>
        )}

        {employeeId && (
          <button
            type="button"
            onClick={() => inputRef.current?.click()}
            disabled={uploading}
            className="absolute -bottom-1 -right-1 w-8 h-8 rounded-full bg-white border border-slate-300 flex items-center justify-center shadow-sm hover:bg-slate-50"
            title={photoUrl ? 'Replace photo' : 'Upload photo'}
          >
            <Camera size={14} className="text-slate-600" />
          </button>
        )}

        <input
          ref={inputRef}
          type="file"
          accept=".jpg,.jpeg,.png"
          onChange={handleFileChange}
          className="hidden"
        />
      </div>

      <p className="text-xs text-slate-500 mt-2">
        {uploading ? 'Uploading...' : photoUrl ? 'Click to view · camera to replace · bin to remove' : employeeId ? 'No photo — click to upload' : 'Save employee to add a photo'}
      </p>

      {error && <p className="text-xs text-danger mt-1">{error}</p>}

      {!photoUrl && employeeId && (
        <p className="text-[11px] text-warning bg-warning-bg px-2 py-0.5 rounded-full mt-1">Photo required</p>
      )}

      {lightboxOpen && photoUrl && (
        <div
          className="fixed inset-0 bg-black/70 flex items-center justify-center z-[60] p-6"
          onClick={() => setLightboxOpen(false)}
        >
          <button type="button" className="absolute top-4 right-4 text-white" onClick={() => setLightboxOpen(false)}>
            <X size={24} />
          </button>
          <img src={photoUrl} alt="" className="max-w-full max-h-full rounded-lg" onClick={(e) => e.stopPropagation()} />
        </div>
      )}
    </div>
  )
}

export default EmployeePhotoUpload