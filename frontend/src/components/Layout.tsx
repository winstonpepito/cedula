import { Link, NavLink, Outlet } from 'react-router-dom'
import { SiteFooter } from './SiteFooter'

export function PublicLayout() {
  return (
    <div className="flex min-h-screen flex-col">
      <header className="absolute inset-x-0 top-0 z-20">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-5 py-5 md:px-8">
          <Link to="/" className="font-display text-2xl font-bold tracking-wide text-white drop-shadow-sm">
            eCedula
          </Link>
          <nav className="flex items-center gap-5 text-sm font-medium text-white/90">
            <NavLink to="/apply" className="hover:text-white">Apply</NavLink>
            <NavLink to="/track" className="hover:text-white">Track</NavLink>
            <NavLink to="/admin/login" className="rounded-full border border-white/30 px-3 py-1.5 hover:bg-white/10">
              Staff
            </NavLink>
          </nav>
        </div>
      </header>
      <div className="flex min-h-screen flex-1 flex-col">
        <div className="flex-1">
          <Outlet />
        </div>
        <div className="relative z-10 -mt-16">
          <SiteFooter variant="dark" />
        </div>
      </div>
    </div>
  )
}

export function SimpleLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex min-h-screen flex-col bg-[radial-gradient(circle_at_top,_#d7efef_0%,_#eef6f5_40%,_#f3efe6_100%)]">
      <header className="border-b border-line/70 bg-white/70 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-5 py-4 md:px-8">
          <Link to="/" className="font-display text-2xl font-bold text-teal">eCedula</Link>
          <nav className="flex gap-4 text-sm font-medium text-ink/80">
            <Link to="/apply">Apply</Link>
            <Link to="/track">Track</Link>
            <Link to="/admin/login">Staff</Link>
          </nav>
        </div>
      </header>
      <main className="mx-auto w-full max-w-6xl flex-1 px-5 py-8 md:px-8 md:py-12">{children}</main>
      <SiteFooter variant="light" />
    </div>
  )
}
