import { useState, type FormEvent } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { SimpleLayout } from '../../components/Layout'
import { Button, Field, Input, PageTitle, Panel } from '../../components/ui'
import { useAuth } from '../../context/AuthContext'

export function AdminLoginPage() {
  const { user, login } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
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
            <Input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required autoComplete="username" />
          </Field>
          <Field label="Password">
            <Input type="password" value={password} onChange={(e) => setPassword(e.target.value)} required autoComplete="current-password" />
          </Field>
          {error ? <p className="text-sm text-accent">{error}</p> : null}
          <Button className="w-full" disabled={busy}>{busy ? 'Signing in…' : 'Sign in'}</Button>
        </form>
      </Panel>
    </SimpleLayout>
  )
}
