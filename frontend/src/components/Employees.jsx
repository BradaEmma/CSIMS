import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import EmployeeModal from './EmployeeModal'
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

function Employees() {
  const [employees, setEmployees] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [modalOpen, setModalOpen] = useState(false)
  const [editingEmployee, setEditingEmployee] = useState(null)
  const [deletingId, setDeletingId] = useState(null)

  function loadEmployees() {
    setLoading(true)
    apiGet('/employees')
      .then((res) => setEmployees(Array.isArray(res) ? res : []))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadEmployees()
  }, [])

  function openAddModal() {
    setEditingEmployee(null)
    setModalOpen(true)
  }

  function openEditModal(employee) {
    setEditingEmployee(employee)
    setModalOpen(true)
  }

  function handleSaved() {
    setModalOpen(false)
    setEditingEmployee(null)
    loadEmployees()
  }

  async function handleDelete(employee) {
    if (!confirm(`Delete ${employee.name}? This cannot be undone.`)) {
      return
    }
    setDeletingId(employee.id)
    try {
      await apiDelete(`/employees/${employee.id}`)
      loadEmployees()
    } catch (err) {
      setError(err.message)
    } finally {
      setDeletingId(null)
    }
  }

  return (
    <AppLayout title="Employees" subtitle="Manage company employees">
      {loading && <p className="text-slate-500">Loading...</p>}

      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

      {!loading && (
        <div className="bg-white rounded-xl border border-slate-200 p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-sm font-bold text-slate-800">
              All Employees ({employees.length})
            </h2>
            <button
              onClick={openAddModal}
              className="bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition"
            >
              + Add Employee
            </button>
          </div>

          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                <th className="pb-2">Name</th>
                <th className="pb-2">Phone</th>
                <th className="pb-2">Department</th>
                <th className="pb-2">Position</th>
                <th className="pb-2">Status</th>
                <th className="pb-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {employees.map((employee) => (
                <tr key={employee.id} className="border-b border-slate-100 last:border-0">
                  <td className="py-2.5 font-medium text-slate-700">{employee.name}</td>
                  <td className="py-2.5 text-slate-600">{employee.phone}</td>
                  <td className="py-2.5 text-slate-600">{employee.department?.name || '—'}</td>
                  <td className="py-2.5 text-slate-600">{employee.position || '—'}</td>
                  <td className="py-2.5"><StatusBadge status={employee.status} /></td>
                  <td className="py-2.5">
                    <button
                      onClick={() => openEditModal(employee)}
                      className="text-primary hover:underline text-xs font-medium mr-3"
                    >
                      Edit
                    </button>
                    <button
                      onClick={() => handleDelete(employee)}
                      disabled={deletingId === employee.id}
                      className="text-danger hover:underline text-xs font-medium disabled:opacity-50"
                    >
                      {deletingId === employee.id ? 'Deleting...' : 'Delete'}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {employees.length === 0 && (
            <p className="text-slate-400 text-sm text-center py-8">No employees found.</p>
          )}
        </div>
      )}

      {modalOpen && (
        <EmployeeModal
          employee={editingEmployee}
          onClose={() => setModalOpen(false)}
          onSaved={handleSaved}
        />
      )}
    </AppLayout>
  )
}

export default Employees