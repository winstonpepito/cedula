import { useState, type FormEvent } from 'react'
import { isAxiosError } from 'axios'
import { Button, Field, Input, PageTitle, Panel } from '../../components/ui'
import { api } from '../../lib/api'
import { useAuth } from '../../context/AuthContext'

export function AdminAccount() {
  const { user } = useAuth()
  const [currentPassword, setCurrentPassword] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [busy, setBusy] = useState(false)
  const [saved, setSaved] = useState(false)
  const [error, setError] = useState('')

  async function onSubmit(e: FormEvent) {
    e.preventDefault()
    setBusy(true)
    setError('')
    setSaved(false)
    try {
      await api.put('/password', {
        current_password: currentPassword,
        password,
        password_confirmation: passwordConfirmation,
      })
      setCurrentPassword('')
      setPassword('')
      setPasswordConfirmation('')
      setSaved(true)
      setTimeout(() => setSaved(false), 2500)
    } catch (err) {
      let message = 'Unable to update password.'
      if (isAxiosError(err)) {
        const data = err.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined
        const firstError = data?.errors ? Object.values(data.errors).flat()[0] : undefined
        message = firstError || data?.message || message
      }
      setError(message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div>
      <PageTitle
        title="Account"
        subtitle="Update the password for your staff login."
      />

      <Panel className="mb-6 max-w-xl">
        <dl className="grid gap-2 text-sm">
          <div className="flex justify-between gap-4">
            <dt className="text-ink/55">Name</dt>
            <dd className="font-semibold">{user?.name}</dd>
          </div>
          <div className="flex justify-between gap-4">
            <dt className="text-ink/55">Email</dt>
            <dd className="font-semibold">{user?.email}</dd>
          </div>
          <div className="flex justify-between gap-4">
            <dt className="text-ink/55">Role</dt>
            <dd className="font-semibold capitalize">{user?.role}</dd>
          </div>
        </dl>
      </Panel>

      <Panel className="max-w-xl">
        <form className="space-y-4" onSubmit={(e) => void onSubmit(e)}>
          <Field label="Current password">
            <Input
              type="password"
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
              required
              autoComplete="current-password"
            />
          </Field>
          <Field label="New password" hint="At least 8 characters.">
            <Input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              minLength={8}
              autoComplete="new-password"
            />
          </Field>
          <Field label="Confirm new password">
            <Input
              type="password"
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
              required
              minLength={8}
              autoComplete="new-password"
            />
          </Field>
          {error ? <p className="text-sm text-accent">{error}</p> : null}
          <div className="flex items-center gap-3">
            <Button type="submit" disabled={busy}>
              {busy ? 'Updating…' : 'Change password'}
            </Button>
            {saved ? <span className="text-sm text-teal-deep">Password updated.</span> : null}
          </div>
        </form>
      </Panel>
    </div>
  )
}
