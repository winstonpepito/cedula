import { useEffect, useState, type FormEvent } from 'react'
import { isAxiosError } from 'axios'
import { Button, Field, Input, PageTitle, Panel, Select } from '../../components/ui'
import { api, formatPeso } from '../../lib/api'
import type { Barangay } from '../../types'

function barangayFeeAmount(row: Barangay): number {
  if (row.deliveryFee?.fee != null && row.deliveryFee.fee !== '') {
    return Number(row.deliveryFee.fee)
  }

  if (row.delivery_fee != null && row.delivery_fee !== '') {
    return Number(row.delivery_fee)
  }

  return 0
}

export function AdminBarangays() {
  const [rows, setRows] = useState<Barangay[]>([])
  const [name, setName] = useState('')
  const [code, setCode] = useState('')
  const [fee, setFee] = useState('50')
  const [defaultBarangayId, setDefaultBarangayId] = useState('')
  const [defaultBusy, setDefaultBusy] = useState(false)
  const [defaultSaved, setDefaultSaved] = useState(false)
  const [defaultError, setDefaultError] = useState('')
  const [feeDrafts, setFeeDrafts] = useState<Record<number, string>>({})
  const [feeSavingId, setFeeSavingId] = useState<number | null>(null)
  const [feeSavedId, setFeeSavedId] = useState<number | null>(null)
  const [feeError, setFeeError] = useState('')

  async function load() {
    const [barangaysRes, defaultRes] = await Promise.all([
      api.get('/admin/barangays'),
      api.get('/admin/barangays/default'),
    ])
    const list = barangaysRes.data.data as Barangay[]
    setRows(list)
    const drafts: Record<number, string> = {}
    for (const row of list) {
      drafts[row.id] = String(barangayFeeAmount(row))
    }
    setFeeDrafts(drafts)
    const id = defaultRes.data.data.default_barangay_id
    setDefaultBarangayId(id != null ? String(id) : '')
  }

  useEffect(() => {
    void load()
  }, [])

  async function create(e: FormEvent) {
    e.preventDefault()
    await api.post('/admin/barangays', {
      name,
      code: code || null,
      delivery_fee: Number(fee),
    })
    setName('')
    setCode('')
    setFee('50')
    await load()
  }

  function feeIsDirty(row: Barangay) {
    const draft = feeDrafts[row.id]
    if (draft == null) return false
    return Number(draft) !== barangayFeeAmount(row)
  }

  async function saveFee(barangay: Barangay) {
    const nextFee = feeDrafts[barangay.id]
    if (nextFee == null || nextFee === '') {
      setFeeError('Enter a delivery fee amount.')
      return
    }
    setFeeSavingId(barangay.id)
    setFeeError('')
    setFeeSavedId(null)
    try {
      await api.put(`/admin/barangays/${barangay.id}`, {
        delivery_fee: Number(nextFee),
      })
      await load()
      setFeeSavedId(barangay.id)
      setTimeout(() => setFeeSavedId((current) => (current === barangay.id ? null : current)), 2000)
    } catch (err) {
      let message = `Unable to save fee for ${barangay.name}.`
      if (isAxiosError(err)) {
        const data = err.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined
        const firstError = data?.errors ? Object.values(data.errors).flat()[0] : undefined
        message = firstError || data?.message || message
      }
      setFeeError(message)
    } finally {
      setFeeSavingId(null)
    }
  }

  async function remove(barangay: Barangay) {
    if (!window.confirm(`Delete barangay "${barangay.name}"?`)) return
    await api.delete(`/admin/barangays/${barangay.id}`)
    await load()
  }

  async function saveDefault(e: FormEvent) {
    e.preventDefault()
    setDefaultBusy(true)
    setDefaultError('')
    setDefaultSaved(false)
    try {
      const { data } = await api.put('/admin/barangays/default', {
        default_barangay_id: defaultBarangayId ? Number(defaultBarangayId) : null,
      })
      const id = data.data.default_barangay_id
      setDefaultBarangayId(id != null ? String(id) : '')
      setDefaultSaved(true)
      setTimeout(() => setDefaultSaved(false), 2000)
    } catch {
      setDefaultError('Unable to save default barangay.')
    } finally {
      setDefaultBusy(false)
    }
  }

  return (
    <div>
      <PageTitle
        title="Barangays & delivery fees"
        subtitle="Edit a barangay delivery fee, then click Save fee. Delivery charges are looked up from the applicant barangay."
      />

      <Panel className="mb-6">
        <form className="grid gap-3 md:grid-cols-[1fr_auto] md:items-end" onSubmit={(e) => void saveDefault(e)}>
          <Field
            label="Default barangay for applications"
            hint="Pre-selected on the apply form address step. Applicants can still change it."
          >
            <Select
              value={defaultBarangayId}
              onChange={(e) => setDefaultBarangayId(e.target.value)}
            >
              <option value="">No default — applicant must choose</option>
              {rows.map((row) => (
                <option key={row.id} value={row.id} disabled={!row.is_active}>
                  {row.name}{row.is_active ? '' : ' (inactive)'}
                </option>
              ))}
            </Select>
          </Field>
          <div className="flex items-center gap-3">
            <Button type="submit" disabled={defaultBusy}>
              {defaultBusy ? 'Saving…' : 'Save default'}
            </Button>
            {defaultSaved ? <span className="text-sm text-teal-deep">Saved.</span> : null}
          </div>
          {defaultError ? <p className="text-sm text-accent md:col-span-2">{defaultError}</p> : null}
        </form>
      </Panel>

      <Panel className="mb-6">
        <form className="grid gap-3 md:grid-cols-4" onSubmit={(e) => void create(e)}>
          <Field label="Name"><Input value={name} onChange={(e) => setName(e.target.value)} required /></Field>
          <Field label="Code"><Input value={code} onChange={(e) => setCode(e.target.value)} /></Field>
          <Field label="Delivery fee"><Input type="number" min="0" value={fee} onChange={(e) => setFee(e.target.value)} /></Field>
          <div className="flex items-end"><Button className="w-full">Add barangay</Button></div>
        </form>
      </Panel>

      {feeError ? <p className="mb-4 text-sm text-accent">{feeError}</p> : null}

      <Panel>
        <div className="overflow-x-auto">
          <table className="min-w-full text-left text-sm">
            <thead className="text-ink/50">
              <tr>
                <th className="py-2 pr-4">Name</th>
                <th className="py-2 pr-4">Code</th>
                <th className="py-2 pr-4">Delivery fee</th>
                <th className="py-2 pr-4">Active</th>
                <th className="py-2 pr-4">Default</th>
                <th className="py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => {
                const dirty = feeIsDirty(row)
                const saving = feeSavingId === row.id
                const saved = feeSavedId === row.id
                const currentFee = barangayFeeAmount(row)
                return (
                  <tr key={row.id} className="border-t border-line/50">
                    <td className="py-3 pr-4 font-semibold">{row.name}</td>
                    <td className="py-3 pr-4">{row.code}</td>
                    <td className="py-3 pr-4">
                      <div className="flex flex-wrap items-center gap-2">
                        <input
                          type="number"
                          min="0"
                          step="0.01"
                          className="w-28 rounded-lg border border-line px-2 py-1"
                          value={feeDrafts[row.id] ?? '0'}
                          onChange={(e) => {
                            setFeeDrafts((prev) => ({ ...prev, [row.id]: e.target.value }))
                            setFeeError('')
                          }}
                          onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                              e.preventDefault()
                              void saveFee(row)
                            }
                          }}
                        />
                        <Button
                          type="button"
                          variant={dirty ? 'primary' : 'secondary'}
                          disabled={saving || !dirty}
                          onClick={() => void saveFee(row)}
                        >
                          {saving ? 'Saving…' : 'Save fee'}
                        </Button>
                        {saved ? <span className="text-xs text-teal-deep">Saved</span> : null}
                      </div>
                      <div className="mt-1 text-xs text-ink/45">
                        Current: {formatPeso(currentFee)}
                      </div>
                    </td>
                    <td className="py-3 pr-4">{row.is_active ? 'Yes' : 'No'}</td>
                    <td className="py-3 pr-4">
                      {String(row.id) === defaultBarangayId ? (
                        <span className="rounded-md bg-teal-soft px-2 py-0.5 text-xs font-semibold text-teal-deep">Default</span>
                      ) : (
                        <span className="text-ink/35">—</span>
                      )}
                    </td>
                    <td className="py-3 text-right">
                      <button
                        type="button"
                        onClick={() => void remove(row)}
                        title={`Delete ${row.name}`}
                        aria-label={`Delete ${row.name}`}
                        className="inline-flex rounded-lg p-2 text-accent transition hover:bg-accent/10"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="h-4 w-4" aria-hidden="true">
                          <path strokeLinecap="round" strokeLinejoin="round" d="M3 6h18M8 6V4.5A1.5 1.5 0 0 1 9.5 3h5A1.5 1.5 0 0 1 16 4.5V6m2 0v13.5A1.5 1.5 0 0 1 16.5 21h-9A1.5 1.5 0 0 1 6 19.5V6m3 4.5v7m6-7v7" />
                        </svg>
                      </button>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      </Panel>
    </div>
  )
}
