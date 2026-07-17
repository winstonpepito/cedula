import { useEffect, useState, type FormEvent } from 'react'
import { Button, Field, Input, PageTitle, Panel, Select } from '../../components/ui'
import { api } from '../../lib/api'
import type { TaxSettings } from '../../types'

export function AdminSettings() {
  const [form, setForm] = useState<TaxSettings | null>(null)
  const [saved, setSaved] = useState(false)

  useEffect(() => {
    void api.get('/admin/tax-settings').then((res) => setForm(res.data.data))
  }, [])

  function set<K extends keyof TaxSettings>(key: K, value: TaxSettings[K]) {
    setForm((prev) => (prev ? { ...prev, [key]: value } : prev))
  }

  async function save(e: FormEvent) {
    e.preventDefault()
    if (!form) return
    const { data } = await api.put('/admin/tax-settings', {
      ...form,
      interest_counts_from_january: Boolean(form.interest_counts_from_january),
      manual_payment_only: Boolean(form.manual_payment_only),
      gcash_number: form.gcash_number || null,
    })
    setForm(data.data)
    setSaved(true)
    setTimeout(() => setSaved(false), 2000)
  }

  if (!form) return <p>Loading settings…</p>

  return (
    <div>
      <PageTitle
        title="Tax formula settings"
        subtitle="These variables drive live calculation and are snapshotted onto each application."
      />
      <Panel>
        <form className="grid gap-4 md:grid-cols-2" onSubmit={(e) => void save(e)}>
          <Field label="Individual base tax"><Input type="number" step="0.01" value={form.individual_base_tax} onChange={(e) => set('individual_base_tax', e.target.value)} /></Field>
          <Field label="Individual rate amount"><Input type="number" step="0.01" value={form.individual_rate_amount} onChange={(e) => set('individual_rate_amount', e.target.value)} /></Field>
          <Field label="Individual rate per"><Input type="number" step="0.01" value={form.individual_rate_per} onChange={(e) => set('individual_rate_per', e.target.value)} /></Field>
          <Field label="Individual additional cap"><Input type="number" step="0.01" value={form.individual_additional_cap} onChange={(e) => set('individual_additional_cap', e.target.value)} /></Field>
          <Field label="Corporation base tax"><Input type="number" step="0.01" value={form.corporation_base_tax} onChange={(e) => set('corporation_base_tax', e.target.value)} /></Field>
          <Field label="Corporation rate amount"><Input type="number" step="0.01" value={form.corporation_rate_amount} onChange={(e) => set('corporation_rate_amount', e.target.value)} /></Field>
          <Field label="Corporation rate per"><Input type="number" step="0.01" value={form.corporation_rate_per} onChange={(e) => set('corporation_rate_per', e.target.value)} /></Field>
          <Field label="Corporation additional cap"><Input type="number" step="0.01" value={form.corporation_additional_cap} onChange={(e) => set('corporation_additional_cap', e.target.value)} /></Field>
          <Field label="Interest rate % per month"><Input type="number" step="0.0001" value={form.interest_rate_percent} onChange={(e) => set('interest_rate_percent', e.target.value)} /></Field>
          <Field label="Deadline month">
            <Select value={form.deadline_month} onChange={(e) => set('deadline_month', Number(e.target.value))}>
              {Array.from({ length: 12 }, (_, i) => i + 1).map((m) => (
                <option key={m} value={m}>{m}</option>
              ))}
            </Select>
          </Field>
          <Field label="Deadline day"><Input type="number" min="1" max="31" value={form.deadline_day} onChange={(e) => set('deadline_day', Number(e.target.value))} /></Field>
          <Field label="Interest counts from January">
            <Select
              value={form.interest_counts_from_january ? '1' : '0'}
              onChange={(e) => set('interest_counts_from_january', e.target.value === '1')}
            >
              <option value="1">Yes</option>
              <option value="0">No</option>
            </Select>
          </Field>
          <Field label="Convenience fee"><Input type="number" step="0.01" min="0" value={form.convenience_fee} onChange={(e) => set('convenience_fee', e.target.value)} /></Field>
          <Field label="Server fee"><Input type="number" step="0.01" min="0" value={form.server_fee} onChange={(e) => set('server_fee', e.target.value)} /></Field>
          <Field label="Payment processor fee"><Input type="number" step="0.01" min="0" value={form.payment_processor_fee} onChange={(e) => set('payment_processor_fee', e.target.value)} /></Field>
          <Field label="Default city"><Input value={form.default_city} onChange={(e) => set('default_city', e.target.value)} /></Field>
          <Field label="Default province"><Input value={form.default_province} onChange={(e) => set('default_province', e.target.value)} /></Field>
          <Field label="Manual payment only" hint="When enabled, applicants can only upload proof of payment (no online checkout).">
            <Select
              value={form.manual_payment_only ? '1' : '0'}
              onChange={(e) => set('manual_payment_only', e.target.value === '1')}
            >
              <option value="0">No — allow online card/GCash</option>
              <option value="1">Yes — proof upload only</option>
            </Select>
          </Field>
          <Field label="GCash number" hint="Shown on the payment form for manual transfers.">
            <Input
              value={form.gcash_number ?? ''}
              onChange={(e) => set('gcash_number', e.target.value)}
              placeholder="09XXXXXXXXX"
            />
          </Field>
          <div className="md:col-span-2">
            <Button type="submit">Save settings</Button>
            {saved ? <span className="ml-3 text-sm text-teal-deep">Saved.</span> : null}
          </div>
        </form>
      </Panel>
    </div>
  )
}
