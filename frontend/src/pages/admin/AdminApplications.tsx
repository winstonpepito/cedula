import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Button, Field, Input, PageTitle, Panel, Select } from '../../components/ui'
import { api, formatPeso, statusLabel } from '../../lib/api'
import { useAuth } from '../../context/AuthContext'

interface AdminApp {
  id: number
  tracking_number: string
  status: string
  delivery_mode: string
  total_due: number | string
  email: string
  first_name?: string
  last_name?: string
  corporation_name?: string
  applicant_type: string
  barangay?: { name: string }
  address_line?: string
  city?: string
  province?: string
  paid_at?: string | null
  payment_proofs?: Array<{
    id: number
    status: string
    original_name?: string
    notes?: string
  }>
  status_logs?: Array<{ id: number; to_status: string; note?: string; created_at: string }>
  documents?: Array<{
    id: number
    type: string
    is_uploaded?: boolean
    original_name?: string | null
  }>
}

export function AdminApplications() {
  const { user } = useAuth()
  const [rows, setRows] = useState<AdminApp[]>([])
  const [q, setQ] = useState('')
  const [status, setStatus] = useState('')
  const isDelivery = user?.role === 'delivery'

  async function load() {
    const { data } = await api.get('/admin/applications', {
      params: { q: q || undefined, status: status || undefined },
    })
    setRows(data.data)
  }

  useEffect(() => {
    void load()
  }, [])

  const statusOptions = isDelivery
    ? ['processing', 'out_for_delivery', 'delivered', 'paid']
    : [
        'awaiting_payment',
        'pending_verification',
        'paid',
        'processing',
        'ready_for_pickup',
        'out_for_delivery',
        'delivered',
      ]

  return (
    <div>
      <PageTitle
        title={isDelivery ? 'Delivery applications' : 'Cedula applications'}
        subtitle={
          isDelivery
            ? 'Open a tracking number to update status. Click the applicant name to download the receipt PDF.'
            : 'Search, verify payments, and update fulfillment status.'
        }
      />
      <Panel className="mb-6">
        <div className="grid gap-3 md:grid-cols-[1fr_200px_auto]">
          <Input placeholder="Search tracking, name, email" value={q} onChange={(e) => setQ(e.target.value)} />
          <Select value={status} onChange={(e) => setStatus(e.target.value)}>
            <option value="">All statuses</option>
            {statusOptions.map((s) => (
              <option key={s} value={s}>{statusLabel(s)}</option>
            ))}
          </Select>
          <Button onClick={() => void load()}>Filter</Button>
        </div>
      </Panel>

      <Panel>
        <div className="overflow-x-auto">
          <table className="min-w-full text-left text-sm">
            <thead className="text-ink/50">
              <tr>
                <th className="py-2 pr-4">Tracking</th>
                <th className="py-2 pr-4">Applicant</th>
                <th className="py-2 pr-4">Mode</th>
                <th className="py-2 pr-4">Status</th>
                <th className="py-2">Total</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.id} className="border-t border-line/50">
                  <td className="py-3 pr-4">
                    <Link className="font-semibold text-teal" to={`/admin/applications/${row.id}`}>
                      {row.tracking_number}
                    </Link>
                  </td>
                  <td className="py-3 pr-4">
                    <a
                      className="font-semibold text-teal hover:underline"
                      href={`/api/admin/applications/${row.id}/summary-pdf`}
                      target="_blank"
                      rel="noreferrer"
                      title="Download PDF with receipt, applicant details, and address"
                    >
                      {row.corporation_name || `${row.first_name || ''} ${row.last_name || ''}`.trim() || 'Applicant'}
                    </a>
                    <div className="text-xs text-ink/45">{row.email}</div>
                  </td>
                  <td className="py-3 pr-4 capitalize">{row.delivery_mode.replaceAll('_', ' ')}</td>
                  <td className="py-3 pr-4 capitalize">{statusLabel(row.status)}</td>
                  <td className="py-3">{formatPeso(row.total_due)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Panel>
    </div>
  )
}

export function AdminApplicationDetail() {
  const { id } = useParams()
  const { user } = useAuth()
  const [app, setApp] = useState<AdminApp | null>(null)
  const [note, setNote] = useState('')
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')
  const [softCopyFile, setSoftCopyFile] = useState<File | null>(null)
  const [uploadingSoftCopy, setUploadingSoftCopy] = useState(false)

  async function load() {
    const { data } = await api.get(`/admin/applications/${id}`)
    setApp(data.data)
  }

  useEffect(() => {
    void load()
  }, [id])

  async function updateStatus(status: string) {
    await api.patch(`/admin/applications/${id}/status`, { status, note })
    setMessage(`Updated to ${statusLabel(status)}`)
    await load()
  }

  async function verify(approve: boolean) {
    await api.post(`/admin/applications/${id}/verify-payment`, {
      approve,
      admin_notes: note || undefined,
    })
    setMessage(approve ? 'Payment verified.' : 'Proof rejected.')
    await load()
  }

  async function uploadSoftCopy() {
    if (!softCopyFile || !app) return
    setUploadingSoftCopy(true)
    setError('')
    setMessage('')
    try {
      const body = new FormData()
      body.append('file', softCopyFile)
      await api.post(`/admin/applications/${app.id}/soft-copy`, body, {
        headers: { 'Content-Type': undefined },
      })
      setSoftCopyFile(null)
      setMessage('CTC soft copy uploaded. The applicant can download it from their receipt page.')
      await load()
    } catch {
      setError('Unable to upload soft copy. Use PDF or image up to 10MB after payment is confirmed.')
    } finally {
      setUploadingSoftCopy(false)
    }
  }

  if (!app) return <p>Loading…</p>

  const name = app.corporation_name || `${app.first_name || ''} ${app.last_name || ''}`.trim()
  const isAdmin = user?.role === 'admin'
  const isDelivery = user?.role === 'delivery'
  const isPaid = Boolean(app.paid_at) || !['awaiting_payment', 'pending_verification', 'cancelled'].includes(app.status)
  const softCopy = (app.documents || []).find((d) => d.type === 'soft_copy_cedula')

  return (
    <div>
      <PageTitle title={app.tracking_number} subtitle={`${name} · ${app.email}`} />
      <div className="grid gap-6 lg:grid-cols-2">
        <Panel>
          <div className="space-y-2 text-sm">
            <div><strong>Status:</strong> <span className="capitalize">{statusLabel(app.status)}</span></div>
            <div><strong>Mode:</strong> <span className="capitalize">{app.delivery_mode.replaceAll('_', ' ')}</span></div>
            <div><strong>Barangay:</strong> {app.barangay?.name}</div>
            <div><strong>Address:</strong> {app.address_line}</div>
            <div><strong>City / Province:</strong> {[app.city, app.province].filter(Boolean).join(', ') || '—'}</div>
            <div><strong>Total:</strong> {formatPeso(app.total_due)}</div>
          </div>

          <div className="mt-4">
            <a
              className="inline-flex"
              href={`/api/admin/applications/${app.id}/summary-pdf`}
              target="_blank"
              rel="noreferrer"
            >
              <Button variant="secondary">Download receipt PDF</Button>
            </a>
          </div>

          <Field label="Note" hint="Optional note for status or verification">
            <Input value={note} onChange={(e) => setNote(e.target.value)} />
          </Field>

          <div className="mt-5 flex flex-wrap gap-2">
            {isAdmin && app.status === 'pending_verification' ? (
              <>
                <Button onClick={() => void verify(true)}>Approve payment proof</Button>
                <Button variant="danger" onClick={() => void verify(false)}>Reject proof</Button>
              </>
            ) : null}
            {isAdmin ? (
              <>
                <Button variant="secondary" onClick={() => void updateStatus('processing')}>Processing</Button>
                <Button variant="secondary" onClick={() => void updateStatus('ready_for_pickup')}>Ready for pickup</Button>
                <Button variant="secondary" onClick={() => void updateStatus('out_for_delivery')}>Out for delivery</Button>
                <Button onClick={() => void updateStatus('delivered')}>Mark delivered</Button>
              </>
            ) : null}
            {isDelivery ? (
              <>
                <Button variant="secondary" onClick={() => void updateStatus('out_for_delivery')}>Out for delivery</Button>
                <Button onClick={() => void updateStatus('delivered')}>Mark delivered</Button>
              </>
            ) : null}
          </div>
          {message ? <p className="mt-4 text-sm text-teal-deep">{message}</p> : null}
          {error ? <p className="mt-4 text-sm text-accent">{error}</p> : null}
        </Panel>

        <Panel>
          {isAdmin ? (
            <div className="mb-6">
              <h3 className="font-display text-xl font-bold">CTC soft copy</h3>
              <p className="mt-1 text-sm text-ink/55">
                Upload the official Community Tax Certificate for the applicant to download on their receipt page.
              </p>
              {softCopy ? (
                <div className="mt-3 rounded-xl bg-mist p-3 text-sm">
                  <div className="font-semibold">
                    {softCopy.is_uploaded ? 'Official upload on file' : 'Generated placeholder on file'}
                  </div>
                  <div className="text-ink/60">{softCopy.original_name || 'soft-copy.pdf'}</div>
                  <a
                    className="mt-2 inline-block font-semibold text-teal"
                    href={`/api/applications/${app.tracking_number}/documents/${softCopy.id}`}
                    target="_blank"
                    rel="noreferrer"
                  >
                    Preview / download
                  </a>
                </div>
              ) : (
                <p className="mt-3 text-sm text-ink/50">No soft copy uploaded yet.</p>
              )}
              {isPaid ? (
                <div className="mt-4 space-y-3">
                  <Field label="Upload file" hint="PDF or image, up to 10MB.">
                    <input
                      type="file"
                      accept=".pdf,image/*"
                      onChange={(e) => setSoftCopyFile(e.target.files?.[0] || null)}
                      className="block w-full text-sm"
                    />
                  </Field>
                  <Button
                    disabled={!softCopyFile || uploadingSoftCopy}
                    onClick={() => void uploadSoftCopy()}
                  >
                    {uploadingSoftCopy ? 'Uploading…' : softCopy ? 'Replace soft copy' : 'Upload soft copy'}
                  </Button>
                </div>
              ) : (
                <p className="mt-3 text-sm text-ink/50">Available after payment is confirmed.</p>
              )}
            </div>
          ) : null}

          <h3 className="font-display text-xl font-bold">Payment proofs</h3>
          <ul className="mt-3 space-y-3 text-sm">
            {(app.payment_proofs || []).map((proof) => (
              <li key={proof.id} className="rounded-xl bg-mist p-3">
                <div className="font-semibold capitalize">{proof.status}</div>
                <div>{proof.original_name}</div>
                {proof.notes ? <div className="text-ink/60">{proof.notes}</div> : null}
                {isAdmin ? (
                  <a
                    className="mt-2 inline-block font-semibold text-teal"
                    href={`/api/admin/applications/${app.id}/proofs/${proof.id}/file`}
                    target="_blank"
                    rel="noreferrer"
                  >
                    View file
                  </a>
                ) : null}
              </li>
            ))}
            {!app.payment_proofs?.length ? <li className="text-ink/50">No proofs uploaded.</li> : null}
          </ul>

          <h3 className="mt-6 font-display text-xl font-bold">Timeline</h3>
          <ol className="mt-3 space-y-2 text-sm">
            {(app.status_logs || []).map((log) => (
              <li key={log.id} className="rounded-xl border border-line/60 px-3 py-2">
                <div className="font-semibold capitalize">{statusLabel(log.to_status)}</div>
                <div className="text-ink/50">{new Date(log.created_at).toLocaleString()}</div>
                {log.note ? <div>{log.note}</div> : null}
              </li>
            ))}
          </ol>
        </Panel>
      </div>
    </div>
  )
}
