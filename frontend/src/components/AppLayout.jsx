import { useNavigate, useLocation, Link } from 'react-router-dom'
import {
  LayoutDashboard,
  Users,
  Building2,
  CalendarDays,
  CheckSquare,
  AlertTriangle,
  BarChart3,
  Wallet,
  CreditCard,
  MinusCircle,
  UserCog,
  ShieldCheck,
  Settings,
  Bell,
  ChevronDown,
  Menu,
  Briefcase,
  FileText,
  Landmark,
  IdCard,
  ClipboardCheck,
  UserPlus,
} from 'lucide-react'
import logo from '../assets/logo.png'

import NotificationBell from './NotificationBell'

const navGroups = [
    {
    label: null,
    items: [
      { label: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
      { label: 'Approvals', path: '/approvals', icon: ClipboardCheck },
    ],
  },
  {
    label: 'Operations',
    items: [
      { label: 'Clients', path: '/clients', icon: Briefcase },
      { label: 'Contracts', path: '/contracts', icon: FileText },
      { label: 'Guards', path: '/guards', icon: Users },
      { label: 'Assignments', path: '/assignments', icon: UserPlus, roles: ['admin', 'supervisor'] },
      { label: 'Sites', path: '/sites', icon: Building2 },
      { label: 'Roster', path: '/roster', icon: CalendarDays },
      { label: 'Attendance', path: '/attendance', icon: CheckSquare },
      { label: 'Incidents', path: '/incidents', icon: AlertTriangle },
      { label: 'Reports', path: '/reports', icon: BarChart3 },
    ],
  },
  {
    label: 'HR',
    items: [
      { label: 'Employees', path: '/employees', icon: IdCard, roles: ['admin'] },
      { label: 'Departments', path: '/departments', icon: Landmark },
    ],
  },
  {
    label: 'Payroll & Finance',
    items: [
      { label: 'Payroll', path: '/payroll', icon: Wallet },
      { label: 'Payments', path: '/payments', icon: CreditCard },
      { label: 'Deductions', path: '/deductions', icon: MinusCircle },
    ],
  },
  {
    label: 'Admin & Settings',
    items: [
      { label: 'Users', path: '/users', icon: UserCog },
      { label: 'Roles & Permissions', path: '/roles', icon: ShieldCheck },
      { label: 'Settings', path: '/settings', icon: Settings },
    ],
  },
]

function getInitials(name) {
  if (!name) return '?'
  return name
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

// An item with no `roles` array is visible to everyone (unchanged default
// behavior). An item WITH a `roles` array is only visible if the current
// user has at least one matching role. This is intentionally generic so
// future roles (hr, finance, technical, etc.) can be added to any item's
// `roles` array later without touching this filtering logic.
function isItemVisible(item, userRoles) {
  if (!item.roles) return true
  return item.roles.some((role) => userRoles.includes(role))
}

function AppLayout({ children, title, subtitle }) {
  const navigate = useNavigate()
  const location = useLocation()

  const user = JSON.parse(localStorage.getItem('csims_user') || '{}')
  const roles = JSON.parse(localStorage.getItem('csims_roles') || '[]')

  const handleLogout = () => {
    localStorage.removeItem('csims_token')
    localStorage.removeItem('csims_user')
    localStorage.removeItem('csims_roles')
    navigate('/login')
  }

  const today = new Date().toLocaleDateString('en-GB', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })

  return (
    <div className="min-h-screen bg-slate-50 flex">
      {/* Sidebar */}
      <aside className="w-64 bg-white border-r border-slate-200 flex flex-col">
        <div className="flex items-center gap-3 px-6 py-5 border-b border-slate-200">
          <img src={logo} alt="Cubs Security" className="w-10 h-10 object-contain" />
          <div>
            <p className="font-extrabold text-primary-dark leading-tight">CSIMS</p>
            <p className="text-[10px] text-slate-400 tracking-wide">CUBS SECURITY</p>
          </div>
        </div>

        <nav className="flex-1 px-3 py-4 overflow-y-auto">
          {navGroups.map((group, gi) => {
            const visibleItems = group.items.filter((item) => isItemVisible(item, roles))
            if (visibleItems.length === 0) return null

            return (
              <div key={gi} className={gi > 0 ? 'mt-5' : ''}>
                {group.label && (
                  <p className="px-3 mb-1 text-[10px] font-bold text-slate-400 tracking-wider uppercase">
                    {group.label}
                  </p>
                )}
                <div className="space-y-1">
                  {visibleItems.map((item) => {
                    const isActive = location.pathname === item.path
                    const Icon = item.icon
                    return (
                      <Link
                        key={item.path}
                        to={item.path}
                        className={`flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition ${
                          isActive
                            ? 'bg-primary-dark text-white'
                            : 'text-slate-600 hover:bg-slate-100'
                        }`}
                      >
                        <Icon size={17} strokeWidth={2} />
                        {item.label}
                      </Link>
                    )
                  })}
                </div>
              </div>
            )
          })}
        </nav>

        <div className="px-4 py-4 border-t border-slate-200 flex items-center gap-3">
          <div className="w-9 h-9 rounded-full bg-primary-dark text-white flex items-center justify-center text-xs font-bold flex-shrink-0">
            {getInitials(user.name)}
          </div>
          <div className="min-w-0 flex-1">
            <p className="text-sm font-semibold text-slate-800 truncate">{user.name || 'User'}</p>
            <p className="text-xs text-slate-400 capitalize truncate">{roles[0] || 'staff'}</p>
          </div>
          <button
            onClick={handleLogout}
            className="text-xs text-danger hover:underline flex-shrink-0"
          >
            Log out
          </button>
        </div>
      </aside>

      {/* Main content */}
      <div className="flex-1 flex flex-col">
        {/* Top bar */}
        <header className="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Menu size={20} className="text-slate-500" />
            <div>
              <h1 className="text-lg font-bold text-slate-900">{title}</h1>
              {subtitle && <p className="text-xs text-slate-500">{subtitle}</p>}
            </div>
          </div>

          <div className="flex items-center gap-5">
            <p className="text-sm text-slate-500 hidden sm:block">{today}</p>

            <NotificationBell />

            <button className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-full bg-primary-dark text-white flex items-center justify-center text-xs font-bold">
                {getInitials(user.name)}
              </div>
              <ChevronDown size={15} className="text-slate-400" />
            </button>
          </div>
        </header>

        {/* Page content */}
        <main className="flex-1 p-6">{children}</main>
      </div>
    </div>
  )
}

export default AppLayout