import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import DepartmentModal from './DepartmentModal'
import ConfirmModal from './ConfirmModal'
import { apiGet, apiDelete } from '../lib/api'

function StatusBadge({ isActive }) {
  const styles = isActive
    ? 'bg-success-bg text-success'
    : 'bg-slate-100 text-slate-500'
  return (
    <span className={`text-xs font-semibold px-2.5 py-1 rounded-full capitalize ${styles}`}>
      {isActive ? 'Active' : 'Inactive'}
    </span>
  )
}

function Departments() {
  const roles = JSON.parse(localStorage.getItem('csims_roles') || '[]')
  const canManage = roles.includes('admin')

  const [departments, setDepartments] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [modalOpen, setModalOpen] = useState(false)
  const [editingDepartment, setEditingDepartment] = useState(null)
  const [deletingId, setDeletingId] = useState(null)
  const [confirmingDepartment, setConfirmingDepartment] = useState(null)

  function loadDepartments() {
    setLoading(true)
    apiGet('/departments')
      .then((res) => setDepartments(Array.isArray(res) ? res : []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadDepartments()
  }, [])

  function openAddModal() {
    setEditingDepartment(null)
    setModalOpen(true)
  }

  function openEditModal(department) {
    setEditingDepartment(department)
    setModalOpen(true)
  }

  function handleSaved() {
    setModalOpen(false)
    setEditingDepartment(null)
    loadDepartments()
  }

    function handleDelete(department) {
    setConfirmingDepartment(department)
  }

  async function confirmDelete() {
    const department = confirmingDepartment
    setConfirmingDepartment(null)
    setDeletingId(department.id)
    try {
      await apiDelete(`/departments/${department.id}`)
      loadDepartments()
    } catch (err) {
      setError(err.message)
    } finally {
      setDeletingId(null)
    }
  }

  return (
    <AppLayout title="Departments" subtitle="Manage company departments">
      {loading && <p className="text-slate-500">Loading...</p>}

      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

      {!loading && (
        <div className="bg-white rounded-xl border border-slate-200 p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-sm font-bold text-slate-800">
              All Departments ({departments.length})
            </h2>
            {canManage && (
              <button
                onClick={openAddModal}
                className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition"
              >
                + Add Department
              </button>
            )}
          </div>

          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                <th className="pb-2">Name</th>
                <th className="pb-2">Description</th>
                <th className="pb-2">Status</th>
                {canManage && <th className="pb-2">Actions</th>}
              </tr>
            </thead>
            <tbody>
              {departments.map((department) => (
                <tr key={department.id} className="border-b border-slate-100 last:border-0">
                  <td className="py-2.5 font-medium text-slate-700">{department.name}</td>
                  <td className="py-2.5 text-slate-600">{department.description || '—'}</td>
                  <td className="py-2.5"><StatusBadge isActive={department.is_active} /></td>
                  {canManage && (
                    <td className="py-2.5">
                      <button
                        onClick={() => openEditModal(department)}
                        className="text-primary hover:underline text-xs font-medium mr-3"
                      >
                        Edit
                      </button>
                      <button
                        onClick={() => handleDelete(department)}
                        disabled={deletingId === department.id}
                        className="text-danger hover:underline text-xs font-medium disabled:opacity-50"
                      >
                        {deletingId === department.id ? 'Deleting...' : 'Delete'}
                      </button>
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>

          {departments.length === 0 && (
            <p className="text-slate-400 text-sm text-center py-8">No departments found.</p>
          )}
        </div>
      )}

            {modalOpen && canManage && (
        <DepartmentModal
          department={editingDepartment}
          onClose={() => setModalOpen(false)}
          onSaved={handleSaved}
        />
      )}

      {confirmingDepartment && canManage && (
        <ConfirmModal
          title="Delete Department"
          message={`Delete ${confirmingDepartment.name}? This cannot be undone.`}
          confirmLabel="Delete"
          onConfirm={confirmDelete}
          onCancel={() => setConfirmingDepartment(null)}
        />
      )}
    </AppLayout>
  )
}

export default Departments