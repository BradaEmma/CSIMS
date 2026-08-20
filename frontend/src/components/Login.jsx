import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import logo from '../assets/logo.png'
import { login } from '../lib/api'

function Login() {
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [rememberMe, setRememberMe] = useState(false)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)

    try {
      const data = await login(email, password)
      localStorage.setItem('csims_token', data.token)
      localStorage.setItem('csims_user', JSON.stringify(data.user))
      localStorage.setItem('csims_roles', JSON.stringify(data.roles))
      navigate('/dashboard')
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen bg-cream flex flex-col relative overflow-hidden">

      {/* Watermark — large, faded logo filling the page background */}
      <img
        src={logo}
        alt=""
        aria-hidden="true"
        className="pointer-events-none select-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[135vh] max-w-[98vw] opacity-[0.06]"
      />

      {/* Top bar — stylised header */}
      <div className="relative z-10 flex flex-col items-center pt-10 pb-4 px-6">
        <span className="text-sm sm:text-base font-extrabold text-primary tracking-[0.25em] uppercase drop-shadow-sm">
          Cubs Security Company Ltd
        </span>
        <h1 className="mt-3 text-3xl sm:text-4xl font-extrabold text-primary-dark tracking-wide leading-tight text-center">
          Information Management System
        </h1>
        <div className="flex items-center gap-3 mt-4">
          <span className="h-px w-10 bg-primary/50"></span>
          <span className="text-sm font-bold text-primary tracking-[0.35em]">CSIMS</span>
          <span className="h-px w-10 bg-primary/50"></span>
        </div>
      </div>

      {/* Login form — pulled up close to the header */}
      <div className="relative z-10 flex-1 flex items-start justify-center px-6 pb-12 pt-2">
        <div className="w-full max-w-sm bg-white rounded-2xl shadow-lg border border-slate-200 p-8">
          <h2 className="text-2xl font-bold text-slate-900">Welcome back</h2>
          <p className="text-sm text-slate-500 mt-1">Sign in to continue to CSIMS</p>

          <form onSubmit={handleSubmit} className="mt-6 space-y-4">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">
                Email / Username
              </label>
              <input
                type="text"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="Enter your email or username"
                className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">
                Password
              </label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Enter your password"
                className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              />
            </div>

            <div className="flex items-center justify-between text-sm">
              <label className="flex items-center gap-2 text-slate-600">
                <input
                  type="checkbox"
                  checked={rememberMe}
                  onChange={(e) => setRememberMe(e.target.checked)}
                  className="rounded border-slate-300 text-primary focus:ring-primary"
                />
                Remember me
              </label>
              <a href="#" className="text-primary hover:underline">
                Forgot password?
              </a>
            </div>

            {error && (
              <p className="text-sm text-danger bg-danger-bg px-3 py-2 rounded-lg">
                {error}
              </p>
            )}

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-primary-dark text-white py-2.5 rounded-lg font-medium hover:bg-primary transition disabled:opacity-60 disabled:cursor-not-allowed"
            >
              {loading ? 'Signing in...' : 'Sign in'}
            </button>
          </form>

          <p className="mt-6 text-xs text-slate-400 text-center">
            🔒 Secure access for authorized users only.
          </p>
        </div>
      </div>

      {/* Footer */}
      <p className="relative z-10 text-center text-xs text-slate-400 pb-6">
        © 2026 Cubs Security Company Limited. All rights reserved.
      </p>
    </div>
  )
}

export default Login