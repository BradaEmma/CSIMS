import { Fragment, useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import SiteModal from './SiteModal'
import PostModal from './PostModal'
import ConfirmModal from './ConfirmModal'
import { apiGet, apiDelete } from '../lib/api'

function StatusBadge({ status }) {
  const styles = {
    active: 'bg-success-bg text-success',
    inactive: 'bg-slate-100 text-slate-500',
  }
  return (
    <span className={`text-xs font-semibold px-2.5 py-1 rounded-full capitalize ${styles[status] || 'bg-slate-100 text-slate-600'}`}>
      {status}
    </span>
  )
}

function Sites() {
  const [sites, setSites] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [modalOpen, setModalOpen] = useState(false)
  const [editingSite, setEditingSite] = useState(null)
  const [deletingId, setDeletingId] = useState(null)
  const [confirmingSite, setConfirmingSite] = useState(null)

  const [expandedSiteId, setExpandedSiteId] = useState(null)
  const [postModalOpen, setPostModalOpen] = useState(false)
  const [postModalSiteId, setPostModalSiteId] = useState(null)
  const [editingPost, setEditingPost] = useState(null)
  const [deletingPostId, setDeletingPostId] = useState(null)
  const [confirmingPost, setConfirmingPost] = useState(null)

  function loadSites() {
    setLoading(true)
    apiGet('/sites')
      .then((res) => setSites(Array.isArray(res) ? res : []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadSites()
  }, [])

  function openAddModal() {
    setEditingSite(null)
    setModalOpen(true)
  }

  function openEditModal(site) {
    setEditingSite(site)
    setModalOpen(true)
  }

  function handleSaved() {
    setModalOpen(false)
    setEditingSite(null)
    loadSites()
  }

  function handleDelete(site) {
    setConfirmingSite(site)
  }

  async function confirmDeleteSite() {
    const site = confirmingSite
    setConfirmingSite(null)
    setDeletingId(site.id)
    try {
      await apiDelete(`/sites/${site.id}`)
      loadSites()
    } catch (err) {
      setError(err.message)
    } finally {
      setDeletingId(null)
    }
  }

  function toggleExpand(siteId) {
    setExpandedSiteId((prev) => (prev === siteId ? null : siteId))
  }

  function openAddPostModal(siteId) {
    setPostModalSiteId(siteId)
    setEditingPost(null)
    setPostModalOpen(true)
  }

  function openEditPostModal(siteId, post) {
    setPostModalSiteId(siteId)
    setEditingPost(post)
    setPostModalOpen(true)
  }

  function handlePostSaved() {
    setPostModalOpen(false)
    setEditingPost(null)
    setPostModalSiteId(null)
    loadSites()
  }

  function handleDeletePost(post) {
    setConfirmingPost(post)
  }

  async function confirmDeletePost() {
    const post = confirmingPost
    setConfirmingPost(null)
    setDeletingPostId(post.id)
    try {
      await apiDelete(`/posts/${post.id}`)
      loadSites()
    } catch (err) {
      setError(err.message)
    } finally {
      setDeletingPostId(null)
    }
  }

  return (
    <AppLayout title="Sites" subtitle="Manage your client sites and coverage needs">
      {loading && <p className="text-slate-500">Loading...</p>}

      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

      {!loading && (
        <div className="bg-white rounded-xl border border-slate-200 p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-sm font-bold text-slate-800">
              All Sites ({sites.length})
            </h2>
            <button
              onClick={openAddModal}
              className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition"
            >
              + Add Site
            </button>
          </div>

          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                <th className="pb-2 w-8"></th>
                <th className="pb-2">Name</th>
                <th className="pb-2">Zone</th>
                <th className="pb-2">Location</th>
                <th className="pb-2">Posts</th>
                <th className="pb-2">Status</th>
                <th className="pb-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {sites.map((site) => {
                const posts = site.posts || []
                const isExpanded = expandedSiteId === site.id
                return (
                  <Fragment key={site.id}>
                    <tr className="border-b border-slate-100 last:border-0">
                      <td className="py-2.5">
                        <button
                          onClick={() => toggleExpand(site.id)}
                          className="text-slate-400 hover:text-slate-700 w-5 h-5 flex items-center justify-center"
                          aria-label={isExpanded ? 'Collapse posts' : 'Expand posts'}
                        >
                          {isExpanded ? '▾' : '▸'}
                        </button>
                      </td>
                      <td className="py-2.5 font-medium text-slate-700">{site.name}</td>
                      <td className="py-2.5 text-slate-600">{site.zone}</td>
                      <td className="py-2.5 text-slate-600">{site.location || '—'}</td>
                      <td className="py-2.5 text-slate-600">{posts.length}</td>
                      <td className="py-2.5"><StatusBadge status={site.status} /></td>
                      <td className="py-2.5">
                        <button
                          onClick={() => openEditModal(site)}
                          className="text-primary hover:underline text-xs font-medium mr-3"
                        >
                          Edit
                        </button>
                        <button
                          onClick={() => handleDelete(site)}
                          disabled={deletingId === site.id}
                          className="text-danger hover:underline text-xs font-medium disabled:opacity-50"
                        >
                          {deletingId === site.id ? 'Deleting...' : 'Delete'}
                        </button>
                      </td>
                    </tr>

                    {isExpanded && (
                      <tr className="border-b border-slate-100 last:border-0">
                        <td></td>
                        <td colSpan={6} className="pb-4 pt-1">
                          <div className="bg-slate-50 border border-slate-200 rounded-lg p-4">
                            <div className="flex items-center justify-between mb-3">
                              <h3 className="text-xs font-bold text-slate-600 uppercase">
                                Posts at {site.name}
                              </h3>
                              <button
                                onClick={() => openAddPostModal(site.id)}
                                className="text-primary hover:underline text-xs font-medium"
                              >
                                + Add Post
                              </button>
                            </div>

                            {posts.length === 0 ? (
                              <p className="text-slate-400 text-xs py-2">
                                No posts configured for this site yet.
                              </p>
                            ) : (
                              <table className="w-full text-xs">
                                <thead>
                                  <tr className="text-left text-slate-400 uppercase border-b border-slate-200">
                                    <th className="pb-2">Post Name</th>
                                    <th className="pb-2">Morning</th>
                                    <th className="pb-2">Night</th>
                                    <th className="pb-2">Status</th>
                                    <th className="pb-2">Actions</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  {posts.map((post) => (
                                    <tr key={post.id} className="border-b border-slate-100 last:border-0">
                                      <td className="py-2 font-medium text-slate-700">{post.name}</td>
                                      <td className="py-2 text-slate-600">{post.morning_guards_required}</td>
                                      <td className="py-2 text-slate-600">{post.night_guards_required}</td>
                                      <td className="py-2"><StatusBadge status={post.status} /></td>
                                      <td className="py-2">
                                        <button
                                          onClick={() => openEditPostModal(site.id, post)}
                                          className="text-primary hover:underline text-xs font-medium mr-3"
                                        >
                                          Edit
                                        </button>
                                        <button
                                          onClick={() => handleDeletePost(post)}
                                          disabled={deletingPostId === post.id}
                                          className="text-danger hover:underline text-xs font-medium disabled:opacity-50"
                                        >
                                          {deletingPostId === post.id ? 'Deleting...' : 'Delete'}
                                        </button>
                                      </td>
                                    </tr>
                                  ))}
                                </tbody>
                              </table>
                            )}
                          </div>
                        </td>
                      </tr>
                    )}
                  </Fragment>
                )
              })}
            </tbody>
          </table>

          {sites.length === 0 && (
            <p className="text-slate-400 text-sm text-center py-8">No sites found.</p>
          )}
        </div>
      )}

      {modalOpen && (
        <SiteModal
          site={editingSite}
          onClose={() => setModalOpen(false)}
          onSaved={handleSaved}
        />
      )}

            {postModalOpen && (
        <PostModal
          siteId={postModalSiteId}
          post={editingPost}
          onClose={() => setPostModalOpen(false)}
          onSaved={handlePostSaved}
        />
      )}

      {confirmingSite && (
        <ConfirmModal
          title="Delete Site"
          message={`Delete ${confirmingSite.name}? This cannot be undone.`}
          confirmLabel="Delete"
          onConfirm={confirmDeleteSite}
          onCancel={() => setConfirmingSite(null)}
        />
      )}

      {confirmingPost && (
        <ConfirmModal
          title="Delete Post"
          message={`Delete post "${confirmingPost.name}"? This cannot be undone.`}
          confirmLabel="Delete"
          onConfirm={confirmDeletePost}
          onCancel={() => setConfirmingPost(null)}
        />
      )}
    </AppLayout>
  )
}

export default Sites