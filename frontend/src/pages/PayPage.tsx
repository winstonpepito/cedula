import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import { SimpleLayout } from '../components/Layout'
import { Button, Field, MoneyRow, PageTitle, Panel } from '../components/ui'
import { api, formatPeso } from '../lib/api'
import type { Application, PublicSettings } from '../types'

export function PayPage() {
  const { tracking } = useParams()
  const [params] = useSearchParams()
  const navigate = useNavigate()
  const [app, setApp] = useState<Application | null>(null)
  const [settings, setSettings] = useState<PublicSettings | null>(null)
  const [loading, setLoading] = useState(true)
  const [busy, setBusy] = useState(false)
  const [notes, setNotes] = useState('')
  const [file, setFile] = useState<File | null>(null)
  const [message, setMessage] = useState('')

  useEffect(() => {
    void load()
  }, [tracking])

  useEffect(() => {
    if (params.get('cancelled') === '1') {
      setMessage('Online payment was cancelled. You can retry or upload a payment proof.')
    }
  }, [params])

  async function load() {
    setLoading(true)
    try {
      const [appRes, settingsRes] = await Promise.all([
        api.get(`/applications/${tracking}`),
        api.get('/settings'),
      ])
      setApp(appRes.data.data)
      setSettings(settingsRes.data.data)
      if (appRes.data.data.is_paid) {
        navigate(`/receipt/${tracking}`, { replace: true })
      }
    } finally {
      setLoading(false)
    }
  }

  async function payOnline() {
    setBusy(true)
    setMessage('')
    try {
      const { data } = await api.post(`/applications/${tracking}/pay`, {
        // Backend defaults include qrph/card/gcash; PayMongo only shows methods Active in the dashboard.
        methods: ['qrph', 'card', 'gcash'],
      })
      if (data.data.mock) {
        navigate(`/pay/${tracking}/mock`)
        return
      }
      window.location.href = data.data.checkout_url
    } catch {
      setMessage('Unable to start online payment. Upload a proof instead.')
    } finally {
      setBusy(false)
    }
  }

  async function uploadProof() {
    if (!file) {
      setMessage('Please choose a screenshot image.')
      return
    }
    setBusy(true)
    setMessage('')
    try {
      const body = new FormData()
      body.append('proof', file)
      if (notes) body.append('notes', notes)
      const { data } = await api.post(`/applications/${tracking}/payment-proof`, body, {
        // Let the browser set multipart boundary — a bare multipart/form-data header breaks uploads.
        headers: { 'Content-Type': undefined },
      })
      setApp(data.data)
      setMessage('Payment proof uploaded. An admin will verify it shortly.')
      navigate(`/receipt/${tracking}`)
    } catch {
      setMessage('Upload failed. Use a JPG/PNG under 5MB.')
    } finally {
      setBusy(false)
    }
  }

  if (loading || !app) {
    return (
      <SimpleLayout>
        <p>Loading payment details…</p>
      </SimpleLayout>
    )
  }

  const manualOnly = Boolean(settings?.manual_payment_only)
  const gcashNumber = settings?.gcash_number?.trim() || ''

  return (
    <SimpleLayout>
      <PageTitle
        title="Complete payment"
        subtitle={
          manualOnly
            ? `Tracking ${app.tracking_number}. Send payment via GCash, then upload your screenshot for verification.`
            : `Tracking ${app.tracking_number}. Pay securely online or upload a screenshot if checkout fails.`
        }
      />

      <div className={`grid gap-6 ${manualOnly ? '' : 'lg:grid-cols-2'}`}>
        <Panel className="animate-rise">
          <h2 className="font-display text-2xl font-bold">Amount due</h2>
          <div className="mt-4">
            <MoneyRow label="Community tax" value={formatPeso(Number(app.community_tax_total))} />
            <MoneyRow label="Interest" value={formatPeso(Number(app.interest_amount))} />
            <MoneyRow label="Delivery fee" value={formatPeso(Number(app.delivery_fee))} />
            <MoneyRow label="Convenience fee" value={formatPeso(Number(app.convenience_fee))} />
            <MoneyRow label="Server fee" value={formatPeso(Number(app.server_fee))} />
            <MoneyRow label="Payment processor fee" value={formatPeso(Number(app.payment_processor_fee))} />
            <div className="mt-3 flex justify-between text-lg font-bold">
              <span>Total</span>
              <span>{formatPeso(Number(app.total_due))}</span>
            </div>
          </div>

          {!manualOnly ? (
            <div className="mt-6 space-y-3">
              <Button className="w-full" disabled={busy} onClick={() => void payOnline()}>
                Pay online (QRPh / card / GCash)
              </Button>
              <p className="text-xs text-ink/50">
                Powered by PayMongo. Only payment methods activated in your PayMongo dashboard will appear at checkout.
              </p>
            </div>
          ) : null}
        </Panel>

        <Panel className="animate-rise delay-1">
          <h2 className="font-display text-2xl font-bold">
            {manualOnly ? 'Pay via GCash & upload proof' : 'Upload payment proof'}
          </h2>
          <p className="mt-1 text-sm text-ink/60">
            {manualOnly
              ? 'Transfer the total amount to the GCash number below, then upload a clear screenshot of your payment.'
              : 'If online payment fails, send a GCash or bank transfer screenshot for admin verification.'}
          </p>

          {gcashNumber ? (
            <div className="mt-5 rounded-2xl border border-teal/20 bg-teal-soft/60 px-4 py-4">
              <div className="text-xs font-semibold uppercase tracking-[0.16em] text-teal-deep">GCash number</div>
              <div className="mt-1 font-display text-2xl font-bold text-teal-deep">{gcashNumber}</div>
              <p className="mt-2 text-sm text-ink/65">
                Amount to send: <strong>{formatPeso(Number(app.total_due))}</strong>
              </p>
            </div>
          ) : null}

          <div className="mt-5 space-y-4">
            <Field label="Screenshot">
              <input
                type="file"
                accept="image/*"
                onChange={(e) => setFile(e.target.files?.[0] || null)}
                className="block w-full text-sm"
              />
            </Field>
            <Field label="Notes (optional)">
              <textarea
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                className="min-h-24 w-full rounded-xl border border-line px-3.5 py-2.5 text-sm outline-none ring-teal/30 focus:ring-2"
              />
            </Field>
            <Button
              variant={manualOnly ? 'primary' : 'secondary'}
              className="w-full"
              disabled={busy}
              onClick={() => void uploadProof()}
            >
              Submit proof
            </Button>
          </div>
          {message ? <p className="mt-4 text-sm text-teal-deep">{message}</p> : null}
          <Link to={`/receipt/${tracking}`} className="mt-4 inline-block text-sm font-semibold text-teal">
            View current status →
          </Link>
        </Panel>
      </div>
    </SimpleLayout>
  )
}
