import { useEffect, useState } from 'react'
import AppLayout from './AppLayout'
import { apiGet, apiPost } from '../lib/api'
import { RefreshCw } from 'lucide-react'

function getStartOfWeek(date) {
  const d = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  const day = d.getDay()
  const daysSinceMonday = day === 0 ? 6 : day - 1
  d.setDate(d.getDate() - daysSinceMonday)
  return d
}

function formatDate(date) {
  return date.toISOString().split('T')[0]
}

function getWeekDates(startDate) {
  const dates = []
  for (let i = 0; i < 7; i++) {
    const d = new Date(startDate)
    d.setDate(d.getDate() + i)
    dates.push(d)
  }
  return dates
}

function Roster() {
  const [weekStart, setWeekStart] = useState(() => getStartOfWeek(new Date()))
  const [assignments, setAssignments] = useState([])
  const [posts, setPosts] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [generating, setGenerating] = useState(false)
  const [message, setMessage] = useState('')

  const weekDates = getWeekDates(weekStart)
  const startStr = formatDate(weekDates[0])
  const endStr = formatDate(weekDates[6])

  function loadData() {
    setLoading(true)
    setError('')
    Promise.all([
      apiGet(`/roster?start_date=${startStr}&end_date=${endStr}`),
      apiGet('/sites'),
    ])
      .then(([rosterRes, sitesRes]) => {
        setAssignments(rosterRes.data || [])

        // Flatten active sites -> their active posts, keeping the parent
        // site's name/id attached to each post row. A site with multiple
        // posts now renders multiple row-pairs, one per post, instead of
        // one row per site.
        const activeSites = Array.isArray(sitesRes)
          ? sitesRes.filter((s) => s.status === 'active')
          : []

        const flattenedPosts = activeSites.flatMap((site) =>
          (site.posts || [])
            .filter((p) => p.status === 'active')
            .map((post) => ({
              ...post,
              site_name: site.name,
            }))
        )

        setPosts(flattenedPosts)
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadData()
  }, [startStr])

  async function handleGenerate() {
    setGenerating(true)
    setMessage('')
    setError('')
    try {
      const res = await apiPost('/roster/generate', { start_date: startStr })
      const shortageCount = res.data?.shortages?.length || 0
      setMessage(
        `Roster generated: ${res.data.created_assignments} assignments created` +
        (shortageCount > 0 ? `, ${shortageCount} shortage(s) found.` : '.')
      )
      loadData()
    } catch (err) {
      setError(err.message)
    } finally {
      setGenerating(false)
    }
  }

  function shiftWeek(days) {
    const next = new Date(weekStart)
    next.setDate(next.getDate() + days)
    setWeekStart(next)
  }

  function cellContent(post, date, shift) {
    const dateStr = formatDate(date)
    const matches = assignments.filter(
      (a) => a.post_id === post.id && a.date === dateStr && a.shift === shift
    )
    const required = shift === 'morning' ? post.morning_guards_required : post.night_guards_required
    const missing = required - matches.length

    return (
      <div className="min-h-[3rem]">
        {matches.map((a) => (
          <div
            key={a.id}
            className={`text-xs px-1.5 py-0.5 rounded mb-1 ${
              a.is_double_shift ? 'bg-info-bg text-info' : 'bg-success-bg text-success'
            }`}
          >
            {a.assigned_guard?.name}
            {a.is_overtime && ' ⚡'}
          </div>
        ))}
        {missing > 0 && (
          <div className="text-xs px-1.5 py-0.5 rounded bg-danger-bg text-danger">
            {missing} short
          </div>
        )}
      </div>
    )
  }

  return (
    <AppLayout title="Roster" subtitle="Weekly guard scheduling across all sites">
      <div className="bg-white rounded-xl border border-slate-200 p-5">
        <div className="flex items-center justify-between mb-4 flex-wrap gap-3">
          <div className="flex items-center gap-3">
            <button
              onClick={() => shiftWeek(-7)}
              className="px-3 py-1.5 text-sm border border-slate-300 rounded-lg hover:bg-slate-50"
            >
              ← Prev
            </button>
            <span className="text-sm font-semibold text-slate-700">
              {startStr} — {endStr}
            </span>
            <button
              onClick={() => shiftWeek(7)}
              className="px-3 py-1.5 text-sm border border-slate-300 rounded-lg hover:bg-slate-50"
            >
              Next →
            </button>
          </div>

          <button
            onClick={handleGenerate}
            disabled={generating}
            className="flex items-center gap-2 bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary transition disabled:opacity-50"
          >
            <RefreshCw size={15} className={generating ? 'animate-spin' : ''} />
            {generating ? 'Generating...' : 'Generate Roster'}
          </button>
        </div>

        {message && (
          <p className="text-sm text-success bg-success-bg px-3 py-2 rounded-lg mb-4">{message}</p>
        )}
        {error && (
          <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg mb-4">{error}</p>
        )}

        {loading ? (
          <p className="text-slate-500">Loading...</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm border-collapse">
              <thead>
                <tr>
                  <th className="text-left text-xs text-slate-400 uppercase p-2 border-b border-slate-200 sticky left-0 bg-white">
                    Site / Post / Shift
                  </th>
                  {weekDates.map((d) => (
                    <th key={formatDate(d)} className="text-xs text-slate-400 uppercase p-2 border-b border-slate-200 text-center min-w-[110px]">
                      {d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' })}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {posts.map((post) => (
                  <>
                    <tr key={`${post.id}-morning`}>
                      <td className="p-2 border-b border-slate-100 font-medium text-slate-700 sticky left-0 bg-white">
                        {post.site_name} — {post.name} <span className="text-xs text-slate-400">(Day)</span>
                      </td>
                      {weekDates.map((d) => (
                        <td key={formatDate(d)} className="p-2 border-b border-slate-100 align-top">
                          {cellContent(post, d, 'morning')}
                        </td>
                      ))}
                    </tr>
                    <tr key={`${post.id}-night`}>
                      <td className="p-2 border-b border-slate-100 font-medium text-slate-700 sticky left-0 bg-white">
                        {post.site_name} — {post.name} <span className="text-xs text-slate-400">(Night)</span>
                      </td>
                      {weekDates.map((d) => (
                        <td key={formatDate(d)} className="p-2 border-b border-slate-100 align-top">
                          {cellContent(post, d, 'night')}
                        </td>
                      ))}
                    </tr>
                  </>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </AppLayout>
  )
}

export default Roster