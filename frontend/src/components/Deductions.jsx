import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import { apiGet, apiPost } from '../lib/api'
import { Plus } from 'lucide-react'

function currentPeriod() {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
}

function Deductions() {
  const [types, setTypes] = useState([])
  const [guards, setGuards] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  // Deduction type form
  const [typeForm, setTypeForm] = useState({
    name: '',
    description: '',
    calculation_type: 'fixed',
    default_value: '',
  })
  const [typeSaving, setTypeSaving] = useState(false)
  const [typeMessage, setTypeMessage] = useState('')
  const [typeError, setTypeError] = useState('')

  // Apply deduction form
  const [deductionForm, setDeductionForm] = useState({
    guard_id: '',
    payroll_deduction_type_id: '',
    amount: '',
    reason: '',
    period: currentPeriod(),
  })
  const [deductionSaving, setDeductionSaving] = useState(false)
  const [deductionMessage, setDeductionMessage] = useState('')
  const [deductionError, setDeductionError] = useState('')

  function loadData() {
    setLoading(true)
    Promise.all([apiGet('/payroll/deduction-types'), apiGet('/guards')])
      .then(([typesRes, guardsRes]) => {
        setTypes(typesRes.data || [])
        setGuards(Array.isArray(guardsRes) ? guardsRes : [])
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadData()
  }, [])

  async function handleCreateType(e) {
    e.preventDefault()
    setTypeMessage('')
    setTypeError('')
    setTypeSaving(true)
    try {
      await apiPost('/payroll/deduction-types', {
        name: typeForm.name,
        description: typeForm.description || null,
        calculation_type: typeForm.calculation_type,
        default_value: Number(typeForm.default_value),
      })
      setTypeMessage('Deduction type created.')
      setTypeForm({ name: '', description: '', calculation_type: 'fixed', default_value: '' })
      loadData()
    } catch (err) {
      setTypeError(err.message)
    } finally {
      setTypeSaving(false)
    }
  }

  async function handleApplyDeduction(e) {
    e.preventDefault()
    setDeductionMessage('')
    setDeductionError('')
    setDeductionSaving(true)
    try {
      await apiPost('/payroll/deductions', {
        guard_id: Number(deductionForm.guard_id),
        payroll_deduction_type_id: Number(deductionForm.payroll_deduction_type_id),
        amount: deductionForm.amount ? Number(deductionForm.amount) : null,
        reason: deductionForm.reason,
        period: deductionForm.period,
      })
      setDeductionMessage('Deduction applied successfully.')
      setDeductionForm({ guard_id: '', payroll_deduction_type_id: '', amount: '', reason: '', period: currentPeriod() })
    } catch (err) {
      setDeductionError(err.message)
    } finally {
      setDeductionSaving(false)
    }
  }

  const selectedType = types.find((t) => t.id === Number(deductionForm.payroll_deduction_type_id))

  return (
    <AppLayout title="Deductions" subtitle="Manage deduction types and apply deductions to guards">
      {error && (
        <p className="text-sm text-danger bg-danger-bg px-4 py-3 rounded-lg mb-4">{error}</p>
      )}

      {loading ? (
        <p className="text-slate-500">Loading...</p>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Deduction Types panel */}
          <div className="bg-white rounded-xl border border-slate-200 p-5">
            <h2 className="text-sm font-bold text-slate-800 mb-4">Deduction Types</h2>

            {typeMessage && (
              <p className="text-sm text-success bg-success-bg px-3 py-2 rounded-lg mb-3">{typeMessage}</p>
            )}
            {typeError && (
              <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mb-3">{typeError}</p>
            )}

            <div className="space-y-2 mb-4 max-h-48 overflow-y-auto">
              {types.map((t) => (
                <div key={t.id} className="flex items-center justify-between text-sm border-b border-slate-100 pb-2">
                  <div>
                    <p className="font-medium text-slate-700">{t.name}</p>
                    <p className="text-xs text-slate-400">
                      {t.calculation_type === 'percentage' ? `${t.default_value}% of daily rate` : `TZS ${Number(t.default_value).toLocaleString()}`}
                    </p>
                  </div>
                </div>
              ))}
              {types.length === 0 && <p className="text-slate-400 text-sm">No deduction types yet.</p>}
            </div>

            <form onSubmit={handleCreateType} className="space-y-3 border-t border-slate-100 pt-4">
              <p className="text-xs font-bold text-slate-500 uppercase">Add New Type</p>
              <input
                placeholder="Name (e.g. Property Damage)"
                value={typeForm.name}
                onChange={(e) => setTypeForm((p) => ({ ...p, name: e.target.value }))}
                required
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              />
              <textarea
                placeholder="Description (optional)"
                value={typeForm.description}
                onChange={(e) => setTypeForm((p) => ({ ...p, description: e.target.value }))}
                rows={2}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
              />
              <div className="flex gap-3">
                <select
                  value={typeForm.calculation_type}
                  onChange={(e) => setTypeForm((p) => ({ ...p, calculation_type: e.target.value }))}
                  className="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                >
                  <option value="fixed">Fixed (TZS)</option>
                  <option value="percentage">Percentage (%)</option>
                </select>
                <input
                  type="number"
                  min="0"
                  step="any"
                  placeholder="Default value"
                  value={typeForm.default_value}
                  onChange={(e) => setTypeForm((p) => ({ ...p, default_value: e.target.value }))}
                  required
                  className="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                />
              </div>
              <button
                type="submit"
                disabled={typeSaving}
                className="flex items-center gap-2 bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition disabled:opacity-50"
              >
                <Plus size={14} />
                {typeSaving ? 'Saving...' : 'Add Type'}
              </button>
            </form>
          </div>

          {/* Apply Deduction panel */}
          <div className="bg-white rounded-xl border border-slate-200 p-5">
            <h2 className="text-sm font-bold text-slate-800 mb-4">Apply Deduction to Guard</h2>

            {deductionMessage && (
              <p className="text-sm text-success bg-success-bg px-3 py-2 rounded-lg mb-3">{deductionMessage}</p>
            )}
            {deductionError && (
              <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mb-3">{deductionError}</p>
            )}

            <form onSubmit={handleApplyDeduction} className="space-y-3">
              <div>
                <label className="text-xs font-medium text-slate-500 block mb-1">Guard</label>
                <select
                  value={deductionForm.guard_id}
                  onChange={(e) => setDeductionForm((p) => ({ ...p, guard_id: e.target.value }))}
                  required
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                >
                  <option value="">Select guard</option>
                  {guards.map((g) => (
                    <option key={g.id} value={g.id}>{g.name}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="text-xs font-medium text-slate-500 block mb-1">Deduction Type</label>
                <select
                  value={deductionForm.payroll_deduction_type_id}
                  onChange={(e) => setDeductionForm((p) => ({ ...p, payroll_deduction_type_id: e.target.value }))}
                  required
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                >
                  <option value="">Select type</option>
                  {types.map((t) => (
                    <option key={t.id} value={t.id}>{t.name}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="text-xs font-medium text-slate-500 block mb-1">
                  Amount Override {selectedType && `(default: ${selectedType.calculation_type === 'percentage' ? selectedType.default_value + '%' : 'TZS ' + Number(selectedType.default_value).toLocaleString()})`}
                </label>
                <input
                  type="number"
                  min="0"
                  step="any"
                  placeholder="Leave blank to use default"
                  value={deductionForm.amount}
                  onChange={(e) => setDeductionForm((p) => ({ ...p, amount: e.target.value }))}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                />
              </div>

              <div>
                <label className="text-xs font-medium text-slate-500 block mb-1">Period</label>
                <input
                  type="month"
                  value={deductionForm.period}
                  onChange={(e) => setDeductionForm((p) => ({ ...p, period: e.target.value }))}
                  required
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                />
              </div>

              <div>
                <label className="text-xs font-medium text-slate-500 block mb-1">Reason</label>
                <textarea
                  value={deductionForm.reason}
                  onChange={(e) => setDeductionForm((p) => ({ ...p, reason: e.target.value }))}
                  rows={2}
                  required
                  placeholder="e.g. Broke a gate remote on 12 Aug"
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary"
                />
              </div>

              <button
                type="submit"
                disabled={deductionSaving}
                className="w-full bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition disabled:opacity-50"
              >
                {deductionSaving ? 'Applying...' : 'Apply Deduction'}
              </button>
            </form>
          </div>
        </div>
      )}
    </AppLayout>
  )
}

export default Deductions