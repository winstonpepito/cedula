import { useEffect, useState, type FormEvent } from 'react'
import { Button, Field, Input, PageTitle, Panel } from '../../components/ui'
import { api, formatPeso } from '../../lib/api'
import type { Barangay } from '../../types'

export function AdminBarangays() {
  const [rows, setRows] = useState<Barangay[]>([])
  const [name, setName] = useState('')
  const [code, setCode] = useState('')
  const [fee, setFee] = useState('50')

  async function load() {
    const { data } = await api.get('/admin/barangays')
    setRows(data.data)
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

  async function updateFee(barangay: Barangay, nextFee: string) {
    await api.put(`/admin/barangays/${barangay.id}`, {
      delivery_fee: Number(nextFee),
    })
    await load()
  }

  async function remove(barangay: Barangay) {
    if (!window.confirm(`Delete barangay "${barangay.name}"?`)) return
    await api.delete(`/admin/barangays/${barangay.id}`)
    await load()
  }

  return (
    <div>
      <PageTitle title="Barangays & delivery fees" subtitle="Delivery charges are looked up from the applicant barangay." />

      <Panel className="mb-6">
        <form className="grid gap-3 md:grid-cols-4" onSubmit={(e) => void create(e)}>
          <Field label="Name"><Input value={name} onChange={(e) => setName(e.target.value)} required /></Field>
          <Field label="Code"><Input value={code} onChange={(e) => setCode(e.target.value)} /></Field>
          <Field label="Delivery fee"><Input type="number" min="0" value={fee} onChange={(e) => setFee(e.target.value)} /></Field>
          <div className="flex items-end"><Button className="w-full">Add barangay</Button></div>
        </form>
      </Panel>

      <Panel>
        <div className="overflow-x-auto">
          <table className="min-w-full text-left text-sm">
            <thead className="text-ink/50">
              <tr>
                <th className="py-2 pr-4">Name</th>
                <th className="py-2 pr-4">Code</th>
                <th className="py-2 pr-4">Fee</th>
                <th className="py-2 pr-4">Active</th>
                <th className="py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.id} className="border-t border-line/50">
                  <td className="py-3 pr-4 font-semibold">{row.name}</td>
                  <td className="py-3 pr-4">{row.code}</td>
                  <td className="py-3 pr-4">
                    <input
                      type="number"
                      className="w-28 rounded-lg border border-line px-2 py-1"
                      defaultValue={Number(row.deliveryFee?.fee ?? 0)}
                      onBlur={(e) => void updateFee(row, e.target.value)}
                    />
                    <div className="text-xs text-ink/45">{formatPeso(row.deliveryFee?.fee ?? 0)}</div>
                  </td>
                  <td className="py-3 pr-4">{row.is_active ? 'Yes' : 'No'}</td>
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
              ))}
            </tbody>
          </table>
        </div>
      </Panel>
    </div>
  )
}
