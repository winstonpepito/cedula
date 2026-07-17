import { QRCodeSVG } from 'qrcode.react'
import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { SimpleLayout } from '../components/Layout'
import { Button, MoneyRow, PageTitle, Panel } from '../components/ui'
import { api, formatPeso, statusLabel } from '../lib/api'
import type { Application } from '../types'

export function ReceiptPage() {
  const { tracking } = useParams()
  const [app, setApp] = useState<Application | null>(null)

  useEffect(() => {
    void api.get(`/applications/${tracking}`).then((res) => setApp(res.data.data))
  }, [tracking])

  if (!app) {
    return <SimpleLayout><p>Loading receipt…</p></SimpleLayout>
  }

  const softCopy = app.documents.find((d) => d.type === 'soft_copy_cedula')
  const receipt = app.documents.find((d) => d.type === 'receipt')

  return (
    <SimpleLayout>
      <PageTitle
        title="Transaction receipt"
        subtitle="Save your tracking number. Scan the QR code anytime to open this record online."
      />

      <div className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <Panel className="animate-rise">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
              <p className="text-sm uppercase tracking-[0.2em] text-ink/45">Tracking</p>
              <h2 className="font-display text-3xl font-bold text-teal">{app.tracking_number}</h2>
              <p className="mt-2 text-sm text-ink/65">{app.applicant_name} · {app.email}</p>
            </div>
            <span className="rounded-full bg-teal-soft px-3 py-1 text-xs font-bold uppercase text-teal-deep">
              {statusLabel(app.status)}
            </span>
          </div>

          <div className="mt-6">
            <MoneyRow label="Basic tax" value={formatPeso(Number(app.base_tax))} />
            <MoneyRow label="Additional tax" value={formatPeso(Number(app.additional_tax))} />
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

          <div className="mt-6 flex flex-wrap gap-3">
            {receipt ? (
              <a href={receipt.download_url} target="_blank" rel="noreferrer">
                <Button>Download receipt PDF</Button>
              </a>
            ) : null}
            {app.can_download_soft_copy && softCopy ? (
              <a href={softCopy.download_url} target="_blank" rel="noreferrer">
                <Button variant="secondary">Download soft copy</Button>
              </a>
            ) : null}
            {!app.is_paid ? (
              <Link to={`/pay/${app.tracking_number}`}>
                <Button variant="secondary">Complete payment</Button>
              </Link>
            ) : null}
          </div>
        </Panel>

        <Panel className="animate-rise delay-1 text-center">
          <h3 className="font-display text-xl font-bold">Scan to view online</h3>
          <div className="mx-auto mt-5 inline-flex rounded-2xl bg-white p-4 shadow-inner">
            <QRCodeSVG value={app.track_url} size={180} />
          </div>
          <p className="mt-4 break-all text-xs text-ink/50">{app.track_url}</p>
          <Link to={`/t/${app.tracking_number}`} className="mt-4 inline-block text-sm font-semibold text-teal">
            Open tracking page →
          </Link>
        </Panel>
      </div>

      <Panel className="mt-6 animate-rise delay-2">
        <h3 className="font-display text-xl font-bold">Status timeline</h3>
        <ol className="mt-4 space-y-3">
          {app.status_logs.map((log) => (
            <li key={log.id} className="rounded-2xl bg-mist px-4 py-3 text-sm">
              <div className="font-semibold capitalize">{statusLabel(log.to_status)}</div>
              <div className="text-ink/55">{new Date(log.created_at).toLocaleString()} · {log.actor_type}</div>
              {log.note ? <div className="mt-1 text-ink/70">{log.note}</div> : null}
            </li>
          ))}
        </ol>
      </Panel>
    </SimpleLayout>
  )
}
