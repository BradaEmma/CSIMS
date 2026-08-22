import { useState } from 'react'
import { apiPost, apiPut } from '../lib/api'

function PostModal({ siteId, post, onClose, onSaved }) {
  const isEdit = !!post

  const [form, setForm] = useState({
    name: post?.name || '',
    morning_guards_required: post?.morning_guards_required ?? 1,
    night_guards_required: post?.night_guards_required ?? 1,
    status: post?.status || 'active',
  })
  const [error, setError] = useState('')
  const [saving, setSaving] = useState(false)

  function handleChange(e) {
    const { name, value } = e.target
    setForm((prev) => ({ ...prev, [name]: value }))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')
    setSaving(true)

    const payload = {
      name: form.name,
      morning_guards_required: Number(form.morning_guards_required),
      night_guards_required: Number(form.night_guards_required),
    }

    if (isEdit) {
      payload.status = form.status
    }

    try {
      if (isEdit) {
        await apiPut(`/posts/${post.id}`, payload)
      } else {
        await apiPost(`/sites/${siteId}/posts`, payload)
      }
      onSaved()
    } catch (err) {
      setError(err.message)
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
      <div className="bg-white rounded-xl w-full max-w-md p-6">
        <h2 className="text-lg font-bold text-slate-800 mb-4">
          {isEdit ? 'Edit Post' : 'Add Post'}
        </h2>

        {error && (
          <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mb-4">{error}</p>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="text-xs font-medium text-slate-500 block mb-1">Post Name</label>
            <input
              name="name"
              value={form.name}
              onChange={handleChange}
              required
              className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
            />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">Morning Guards Needed</label>
              <input
                type="number"
                min="0"
                name="morning_guards_required"
                value={form.morning_guards_required}
                onChange={handleChange}
                required
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              />
            </div>
            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">Night Guards Needed</label>
              <input
                type="number"
                min="0"
                name="night_guards_required"
                value={form.night_guards_required}
                onChange={handleChange}
                required
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              />
            </div>
          </div>

          {isEdit && (
            <div>
              <label className="text-xs font-medium text-slate-500 block mb-1">Status</label>
              <select
                name="status"
                value={form.status}
                onChange={handleChange}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          )}

          <div className="flex justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={onClose}
              className="text-sm font-medium text-slate-600 px-4 py-2 rounded-lg hover:bg-slate-100"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={saving}
              className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition disabled:opacity-50"
            >
              {saving ? 'Saving...' : isEdit ? 'Save Changes' : 'Add Post'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default PostModal