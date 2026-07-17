import { useState, type FormEvent } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { SimpleLayout } from '../../components/Layout'
import { Button, Field, Input, PageTitle, Panel } from '../../components/ui'
import { useAuth } from '../../context/AuthContext'

export function AdminLoginPage() {
  const { user, login } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('admin@ecedula.local')
  const [password, setPassword] = useState('password')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  if (user) {
    return <Navigate to="/admin" replace />
  }

  async function onSubmit(e: FormEvent) {
    e.preventDefault()
    setBusy(true)
    setError('')
    try {
      await login(email, password)
      navigate('/admin')
    } catch {
      setError('Invalid credentials.')
    } finally {
      setBusy(false)
    }
  }

  return (
    <SimpleLayout>
      <PageTitle title="Staff login" subtitle="Admins and delivery staff access reports, fees, and fulfillment updates." />
      <Panel className="max-w-md animate-rise">
        <form className="space-y-4" onSubmit={(e) => void onSubmit(e)}>
          <Field label="Email">
            <Input type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
          </Field>
          <Field label="Password">
            <Input type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
          </Field>
          {error ? <p className="text-sm text-accent">{error}</p> : null}
          <Button className="w-full" disabled={busy}>{busy ? 'Signing in…' : 'Sign in'}</Button>
        </form>
        <p className="mt-4 text-xs text-ink/50">
          Demo: admin@ecedula.local / password · delivery@ecedula.local / password
        </p>
      </Panel>
    </SimpleLayout>
  )
}
