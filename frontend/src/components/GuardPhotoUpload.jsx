import { useEffect, useState, useRef } from 'react'
import { Camera, X } from 'lucide-react'

const API_BASE_URL = 'http://127.0.0.1:8000/api/v1'
const STORAGE_URL = API_BASE_URL.replace('/api/v1', '') + '/storage'

function authHeaders() {
  const token = localStorage.getItem('csims_token')
  return { 'Authorization': `Bearer ${token}` }
}

function GuardPhotoUpload({ guardId, guardName }) {
  const [document, setDocument] = useState(null)
  const [uploading, setUploading] = useState(false)
  const [error, setError] = useState('')
  const [lightboxOpen, setLightboxOpen] = useState(false)
  const inputRef = useRef(null)

  function load() {
    if (!guardId) return
    fetch(`${API_BASE_URL}/documents?documentable_type=guard&documentable_id=${guardId}&type=passport_photo`, {
      headers: authHeaders(),
    })
      .then((res) => res.json())
      .then((res) => setDocument((res.data || [])[0] || null))
      .catch(() => {})
  }

  useEffect(() => {
    load()
  }, [guardId])

  async function handleFileChange(e) {
    const file = e.target.files[0]
    if (!file) return

    setError('')
    setUploading(true)

    try {
      if (document) {
        await fetch(`${API_BASE_URL}/documents/${document.id}`, {
          method: 'DELETE',
          headers: authHeaders(),
        })
      }

      const formData = new FormData()
      formData.append('file', file)
      formData.append('documentable_type', 'guard')
      formData.append('documentable_id', guardId)
      formData.append('type', 'passport_photo')

      const res = await fetch(`${API_BASE_URL}/documents`, {
        method: 'POST',
        headers: authHeaders(),
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

  const initials = (guardName || '?')
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
          disabled={!guardId}
          className="w-24 h-24 rounded-full overflow-hidden bg-primary-dark flex items-center justify-center border-2 border-slate-200 disabled:opacity-50"
        >
          {photoUrl ? (
            <img src={photoUrl} alt="" className="w-full h-full object-cover" />
          ) : (
            <span className="text-white text-2xl font-bold">{initials}</span>
          )}
        </button>

        {guardId && (
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
        {uploading ? 'Uploading...' : photoUrl ? 'Click to view · camera icon to replace' : guardId ? 'No photo — click to upload' : 'Save guard to add a photo'}
      </p>

      {error && <p className="text-xs text-danger mt-1">{error}</p>}

      {!photoUrl && guardId && (
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

export default GuardPhotoUpload