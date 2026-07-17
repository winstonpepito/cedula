import { Link, NavLink, Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import { SiteFooter } from '../../components/SiteFooter'
import { Button } from '../../components/ui'

export function AdminShell() {
  const { user, loading, logout } = useAuth()

  if (loading) {
    return <div className="grid min-h-screen place-items-center">Loading…</div>
  }

  if (!user) {
    return <Navigate to="/admin/login" replace />
  }

  const isAdmin = user.role === 'admin'

  return (
    <div className="flex min-h-screen flex-col bg-[linear-gradient(180deg,#eef6f5_0%,#f7f4ee_100%)]">
      <header className="border-b border-line/70 bg-white/80 backdrop-blur">
        <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-4 md:px-8">
          <div className="flex items-center gap-6">
            <Link to="/admin" className="font-display text-2xl font-bold text-teal">eCedula</Link>
            <nav className="flex flex-wrap gap-3 text-sm font-semibold text-ink/70">
              <NavLink to="/admin" end className={({ isActive }) => isActive ? 'text-teal' : ''}>Dashboard</NavLink>
              <NavLink to="/admin/applications" className={({ isActive }) => isActive ? 'text-teal' : ''}>Applications</NavLink>
              {isAdmin ? (
                <>
                  <NavLink to="/admin/barangays" className={({ isActive }) => isActive ? 'text-teal' : ''}>Barangays</NavLink>
                  <NavLink to="/admin/homepage" className={({ isActive }) => isActive ? 'text-teal' : ''}>Homepage</NavLink>
                  <NavLink to="/admin/settings" className={({ isActive }) => isActive ? 'text-teal' : ''}>Tax settings</NavLink>
                </>
              ) : null}
            </nav>
          </div>
          <div className="flex items-center gap-3 text-sm">
            <span className="text-ink/60">{user.name} · {user.role}</span>
            <Button variant="secondary" onClick={() => void logout()}>Logout</Button>
          </div>
        </div>
      </header>
      <main className="mx-auto w-full max-w-7xl flex-1 px-5 py-8 md:px-8">
        <Outlet />
      </main>
      <SiteFooter variant="admin" />
    </div>
  )
}
