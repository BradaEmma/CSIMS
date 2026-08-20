import { useEffect, useRef, useState } from 'react'
import { Bell, AlertTriangle } from 'lucide-react'
import { getUnreadNotifications, markNotificationRead } from '../lib/api'

function timeAgo(dateString) {
  const seconds = Math.floor((new Date() - new Date(dateString)) / 1000)
  if (seconds < 60) return 'just now'
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  return `${days}d ago`
}

function NotificationBell() {
  const [notifications, setNotifications] = useState([])
  const [open, setOpen] = useState(false)
  const [loading, setLoading] = useState(true)
  const ref = useRef(null)

  function loadNotifications() {
    setLoading(true)
    getUnreadNotifications()
      .then((res) => setNotifications(res.data || []))
      .catch(() => {})
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    loadNotifications()
    const interval = setInterval(loadNotifications, 60000) // refresh every 60s
    return () => clearInterval(interval)
  }, [])

  useEffect(() => {
    function handleClickOutside(e) {
      if (ref.current && !ref.current.contains(e.target)) {
        setOpen(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  async function handleMarkRead(id) {
    try {
      await markNotificationRead(id)
      setNotifications((prev) => prev.filter((n) => n.id !== id))
    } catch {
      // fail silently — not critical, will resync on next poll
    }
  }

  return (
    <div className="relative" ref={ref}>
      <button onClick={() => setOpen((o) => !o)} className="relative text-slate-500 hover:text-slate-700">
        <Bell size={19} />
        {notifications.length > 0 && (
          <span className="absolute -top-1.5 -right-1.5 w-4 h-4 bg-danger text-white text-[9px] font-bold rounded-full flex items-center justify-center">
            {notifications.length > 9 ? '9+' : notifications.length}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute right-0 mt-2 w-80 bg-white rounded-xl border border-slate-200 shadow-lg z-50 max-h-96 overflow-y-auto">
          <div className="px-4 py-3 border-b border-slate-100">
            <p className="text-sm font-bold text-slate-800">Notifications</p>
          </div>

          {loading ? (
            <p className="text-sm text-slate-400 px-4 py-6 text-center">Loading...</p>
          ) : notifications.length === 0 ? (
            <p className="text-sm text-slate-400 px-4 py-6 text-center">No unread notifications.</p>
          ) : (
            notifications.map((n) => (
              <div key={n.id} className="px-4 py-3 border-b border-slate-50 last:border-0 hover:bg-slate-50">
                <div className="flex items-start gap-2">
                  <AlertTriangle size={15} className="text-warning mt-0.5 flex-shrink-0" />
                  <div className="min-w-0 flex-1">
                    <p className="text-sm font-medium text-slate-800">{n.data.title}</p>
                    <p className="text-xs text-slate-500 mt-0.5">{n.data.message}</p>
                    <div className="flex items-center justify-between mt-1.5">
                      <span className="text-[10px] text-slate-400">{timeAgo(n.created_at)}</span>
                      <button
                        onClick={() => handleMarkRead(n.id)}
                        className="text-[10px] text-primary hover:underline font-medium"
                      >
                        Mark as read
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
      )}
    </div>
  )
}

export default NotificationBell