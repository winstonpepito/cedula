import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { SimpleLayout } from '../components/Layout'
import { Button, PageTitle, Panel } from '../components/ui'
import { api } from '../lib/api'

export function MockPayPage() {
  const { tracking } = useParams()
  const navigate = useNavigate()
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')

  async function confirm() {
    setBusy(true)
    setError('')
    try {
      await api.post(`/applications/${tracking}/mock-pay`)
      navigate(`/receipt/${tracking}?paid=1`)
    } catch {
      setError('Mock payment failed.')
    } finally {
      setBusy(false)
    }
  }

  return (
    <SimpleLayout>
      <PageTitle
        title="Mock checkout"
        subtitle="PayMongo keys are not configured. This local checkout simulates a successful card/GCash payment."
      />
      <Panel className="max-w-lg animate-rise">
        <p className="text-sm text-ink/70">
          Tracking <strong>{tracking}</strong>
        </p>
        <div className="mt-6 flex gap-3">
          <Button disabled={busy} onClick={() => void confirm()}>
            {busy ? 'Processing…' : 'Simulate successful payment'}
          </Button>
          <Button variant="secondary" onClick={() => navigate(`/pay/${tracking}`)}>
            Cancel
          </Button>
        </div>
        {error ? <p className="mt-4 text-sm text-accent">{error}</p> : null}
      </Panel>
    </SimpleLayout>
  )
}
