import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { SimpleLayout } from '../components/Layout'
import { Button, Field, Input, PageTitle, Panel } from '../components/ui'
import { api } from '../lib/api'
import { ReceiptPage } from './ReceiptPage'

export function TrackPage() {
  const { tracking } = useParams()
  const navigate = useNavigate()
  const [value, setValue] = useState(tracking || '')

  if (tracking) {
    return <ReceiptPage />
  }

  return (
    <SimpleLayout>
      <PageTitle
        title="Track your cedula"
        subtitle="Enter the tracking number from your receipt or scan the QR code."
      />
      <Panel className="max-w-xl animate-rise">
        <Field label="Tracking number">
          <Input
            placeholder="ECD-2026-XXXXXXXX"
            value={value}
            onChange={(e) => setValue(e.target.value.toUpperCase())}
          />
        </Field>
        <Button
          className="mt-5"
          onClick={() => {
            if (value.trim()) navigate(`/t/${value.trim()}`)
          }}
        >
          Track
        </Button>
      </Panel>
    </SimpleLayout>
  )
}

export function TokenReceiptPage() {
  const { token } = useParams()
  const navigate = useNavigate()
  const [error, setError] = useState('')

  useEffect(() => {
    if (!token) return
    void api
      .get(`/r/${token}`)
      .then((res) => navigate(`/receipt/${res.data.data.tracking_number}`, { replace: true }))
      .catch(() => setError('Transaction not found.'))
  }, [token, navigate])

  if (error) {
    return (
      <SimpleLayout>
        <p>{error}</p>
      </SimpleLayout>
    )
  }

  return (
    <SimpleLayout>
      <p>Loading transaction…</p>
    </SimpleLayout>
  )
}
