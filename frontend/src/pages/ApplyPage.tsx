import { useEffect, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { SimpleLayout } from '../components/Layout'
import { Button, Field, Input, MoneyRow, PageTitle, Panel, Select } from '../components/ui'
import { api, formatPeso } from '../lib/api'
import type { ApplicantType, Barangay, Breakdown, DeliveryMode } from '../types'

const steps = ['Type', 'Details', 'Address', 'Delivery', 'Review']

export function ApplyPage() {
  const navigate = useNavigate()
  const [step, setStep] = useState(0)
  const [barangays, setBarangays] = useState<Barangay[]>([])
  const [breakdown, setBreakdown] = useState<Breakdown | null>(null)
  const [loadingCalc, setLoadingCalc] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')

  const [form, setForm] = useState({
    applicant_type: 'individual' as ApplicantType,
    first_name: '',
    middle_name: '',
    last_name: '',
    corporation_name: '',
    email: '',
    phone: '',
    tin: '',
    birthdate: '',
    civil_status: '',
    occupation: '',
    address_line: '',
    city: '',
    province: '',
    barangay_id: '',
    delivery_mode: 'soft_copy' as DeliveryMode,
    monthly_salary: '20000',
    thirteenth_month: '20000',
    other_bonuses: '50000',
    property_value: '1000000',
    gross_receipts: '5000000',
  })

  useEffect(() => {
    void Promise.all([
      api.get('/barangays').then((res) => res.data.data as Barangay[]),
      api.get('/settings').then((res) => res.data.data),
    ]).then(([list, settings]) => {
      setBarangays(list)
      setForm((prev) => {
        const city = prev.city || settings.default_city || 'Cebu City'
        const province = prev.province || settings.default_province || 'Cebu'
        let barangay_id = prev.barangay_id
        if (!barangay_id && settings.default_barangay_id != null) {
          const exists = list.some((b) => b.id === settings.default_barangay_id)
          if (exists) {
            barangay_id = String(settings.default_barangay_id)
          }
        }
        return { ...prev, city, province, barangay_id }
      })
    })
  }, [])

  const selectedBarangay = useMemo(
    () => barangays.find((b) => String(b.id) === String(form.barangay_id)),
    [barangays, form.barangay_id],
  )

  useEffect(() => {
    if (step < 3) return
    const timer = setTimeout(() => {
      void recalculate()
    }, 250)
    return () => clearTimeout(timer)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [
    step,
    form.applicant_type,
    form.delivery_mode,
    form.barangay_id,
    form.monthly_salary,
    form.thirteenth_month,
    form.other_bonuses,
    form.property_value,
    form.gross_receipts,
  ])

  const set = (key: string, value: string) => setForm((prev) => ({ ...prev, [key]: value }))

  async function recalculate() {
    setLoadingCalc(true)
    setError('')
    try {
      const { data } = await api.post('/calculate', {
        applicant_type: form.applicant_type,
        delivery_mode: form.delivery_mode,
        barangay_id: form.barangay_id || null,
        monthly_salary: Number(form.monthly_salary || 0),
        thirteenth_month: Number(form.thirteenth_month || 0),
        other_bonuses: Number(form.other_bonuses || 0),
        property_value: Number(form.property_value || 0),
        gross_receipts: Number(form.gross_receipts || 0),
      })
      setBreakdown(data)
    } catch {
      setBreakdown(null)
    } finally {
      setLoadingCalc(false)
    }
  }

  function canNext() {
    if (step === 0) return !!form.applicant_type
    if (step === 1) {
      if (form.applicant_type === 'individual') {
        return form.first_name && form.last_name && form.email && form.monthly_salary !== ''
      }
      return form.corporation_name && form.email && form.property_value !== '' && form.gross_receipts !== ''
    }
    if (step === 2) return form.address_line && form.barangay_id
    if (step === 3) return !!form.delivery_mode
    return true
  }

  async function submit() {
    setSubmitting(true)
    setError('')
    try {
      const payload = {
        ...form,
        barangay_id: Number(form.barangay_id),
        monthly_salary: Number(form.monthly_salary || 0),
        thirteenth_month: Number(form.thirteenth_month || 0),
        other_bonuses: Number(form.other_bonuses || 0),
        property_value: Number(form.property_value || 0),
        gross_receipts: Number(form.gross_receipts || 0),
        birthdate: form.birthdate || null,
      }
      const { data } = await api.post('/applications', payload)
      navigate(`/pay/${data.data.tracking_number}`)
    } catch (err: unknown) {
      const message =
        (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })
          ?.response?.data?.message ||
        'Unable to submit application. Please check the form.'
      setError(message)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <SimpleLayout>
      <PageTitle
        title="Apply for Cedula"
        subtitle="Complete the guided form. Totals are calculated from official community tax rules and your selected delivery mode. Applications and claims are available only during weekdays office hours. Applications submitted in the afternoon are processed the following day."
      />

      <div className="mb-6 flex flex-wrap gap-2">
        {steps.map((label, index) => (
          <div
            key={label}
            className={`rounded-full px-3 py-1 text-xs font-semibold ${
              index === step
                ? 'bg-teal text-white'
                : index < step
                  ? 'bg-teal-soft text-teal-deep'
                  : 'bg-white text-ink/50 border border-line'
            }`}
          >
            {index + 1}. {label}
          </div>
        ))}
      </div>

      <div className="grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
        <Panel className="animate-rise">
          {step === 0 && (
            <div className="space-y-4">
              <h2 className="font-display text-2xl font-bold">Who is applying?</h2>
              <div className="grid gap-3 md:grid-cols-2">
                {(['individual', 'corporation'] as ApplicantType[]).map((type) => (
                  <button
                    key={type}
                    type="button"
                    onClick={() => set('applicant_type', type)}
                    className={`rounded-2xl border p-5 text-left transition ${
                      form.applicant_type === type
                        ? 'border-teal bg-teal-soft'
                        : 'border-line bg-white hover:border-teal/40'
                    }`}
                  >
                    <div className="font-semibold capitalize">{type}</div>
                    <p className="mt-1 text-sm text-ink/60">
                      {type === 'individual'
                        ? 'For employed or self-employed persons using annual income.'
                        : 'For corporations based on property and gross receipts.'}
                    </p>
                  </button>
                ))}
              </div>
            </div>
          )}

          {step === 1 && (
            <div className="space-y-4">
              <h2 className="font-display text-2xl font-bold">Applicant details</h2>
              {form.applicant_type === 'individual' ? (
                <div className="grid gap-4 md:grid-cols-2">
                  <Field label="First name"><Input value={form.first_name} onChange={(e) => set('first_name', e.target.value)} /></Field>
                  <Field label="Middle name"><Input value={form.middle_name} onChange={(e) => set('middle_name', e.target.value)} /></Field>
                  <Field label="Last name"><Input value={form.last_name} onChange={(e) => set('last_name', e.target.value)} /></Field>
                  <Field label="Email"><Input type="email" value={form.email} onChange={(e) => set('email', e.target.value)} /></Field>
                  <Field label="Phone"><Input value={form.phone} onChange={(e) => set('phone', e.target.value)} /></Field>
                  <Field label="TIN"><Input value={form.tin} onChange={(e) => set('tin', e.target.value)} /></Field>
                  <Field label="Birthdate"><Input type="date" value={form.birthdate} onChange={(e) => set('birthdate', e.target.value)} /></Field>
                  <Field label="Civil status">
                    <Select value={form.civil_status} onChange={(e) => set('civil_status', e.target.value)}>
                      <option value="">Select civil status</option>
                      <option value="Single">Single</option>
                      <option value="Married">Married</option>
                    </Select>
                  </Field>
                  <Field label="Occupation"><Input value={form.occupation} onChange={(e) => set('occupation', e.target.value)} /></Field>
                  <Field label="Monthly salary"><Input type="number" min="0" value={form.monthly_salary} onChange={(e) => set('monthly_salary', e.target.value)} /></Field>
                  <Field label="13th month pay"><Input type="number" min="0" value={form.thirteenth_month} onChange={(e) => set('thirteenth_month', e.target.value)} /></Field>
                  <Field label="Other allowances / bonus"><Input type="number" min="0" value={form.other_bonuses} onChange={(e) => set('other_bonuses', e.target.value)} /></Field>
                </div>
              ) : (
                <div className="grid gap-4 md:grid-cols-2">
                  <Field label="Corporation name"><Input value={form.corporation_name} onChange={(e) => set('corporation_name', e.target.value)} /></Field>
                  <Field label="Email"><Input type="email" value={form.email} onChange={(e) => set('email', e.target.value)} /></Field>
                  <Field label="Phone"><Input value={form.phone} onChange={(e) => set('phone', e.target.value)} /></Field>
                  <Field label="TIN"><Input value={form.tin} onChange={(e) => set('tin', e.target.value)} /></Field>
                  <Field label="Real property owned"><Input type="number" min="0" value={form.property_value} onChange={(e) => set('property_value', e.target.value)} /></Field>
                  <Field label="Gross receipts / income"><Input type="number" min="0" value={form.gross_receipts} onChange={(e) => set('gross_receipts', e.target.value)} /></Field>
                </div>
              )}
            </div>
          )}

          {step === 2 && (
            <div className="space-y-4">
              <h2 className="font-display text-2xl font-bold">Address</h2>
              <p className="text-sm text-ink/60">Barangay is required — delivery fees are based on it.</p>
              <div className="grid gap-4 md:grid-cols-2">
                <Field label="Street / house no.">
                  <Input value={form.address_line} onChange={(e) => set('address_line', e.target.value)} />
                </Field>
                <Field label="Barangay">
                  <Select value={form.barangay_id} onChange={(e) => set('barangay_id', e.target.value)}>
                    <option value="">Select barangay</option>
                    {barangays.map((b) => (
                      <option key={b.id} value={b.id}>
                        {b.name} {b.delivery_fee != null ? `(delivery ${formatPeso(b.delivery_fee)})` : ''}
                      </option>
                    ))}
                  </Select>
                </Field>
                <Field label="City / Municipality" hint="Defined in admin settings">
                  <Input value={form.city} readOnly className="bg-mist text-ink/80" />
                </Field>
                <Field label="Province" hint="Defined in admin settings">
                  <Input value={form.province} readOnly className="bg-mist text-ink/80" />
                </Field>
              </div>
            </div>
          )}

          {step === 3 && (
            <div className="space-y-4">
              <h2 className="font-display text-2xl font-bold">How do you want to receive it?</h2>
              <p className="rounded-2xl border border-teal/20 bg-teal-soft/50 px-4 py-3 text-sm leading-relaxed text-ink/75">
                Claims and deliveries are available on working days only. Applications submitted in the afternoon
                will be available for claim or delivery the following working day.
              </p>
              <div className="grid gap-3">
                {([
                  ['soft_copy', 'Soft copy', 'Digital certificate via download after payment. No delivery fee.'],
                  ['pickup', 'Pickup', 'Claim the physical document at the office on working days. No delivery fee.'],
                  ['delivery', 'Delivered', `Doorstep delivery on working days. Fee for ${selectedBarangay?.name || 'selected barangay'}: ${formatPeso(selectedBarangay?.delivery_fee || 0)}`],
                ] as const).map(([mode, title, desc]) => (
                  <button
                    key={mode}
                    type="button"
                    onClick={() => set('delivery_mode', mode)}
                    className={`rounded-2xl border p-5 text-left transition ${
                      form.delivery_mode === mode ? 'border-teal bg-teal-soft' : 'border-line hover:border-teal/40'
                    }`}
                  >
                    <div className="font-semibold">{title}</div>
                    <p className="mt-1 text-sm text-ink/60">{desc}</p>
                  </button>
                ))}
              </div>
            </div>
          )}

          {step === 4 && (
            <div className="space-y-4">
              <h2 className="font-display text-2xl font-bold">Review & submit</h2>
              <div className="rounded-2xl bg-mist p-4 text-sm leading-relaxed text-ink/80">
                <div><strong>Applicant:</strong> {form.applicant_type === 'individual' ? `${form.first_name} ${form.last_name}` : form.corporation_name}</div>
                <div><strong>Email:</strong> {form.email}</div>
                <div><strong>Address:</strong> {form.address_line}, {selectedBarangay?.name}</div>
                <div><strong>Mode:</strong> {form.delivery_mode.replaceAll('_', ' ')}</div>
              </div>
              {error ? <p className="text-sm text-accent">{error}</p> : null}
            </div>
          )}

          <div className="mt-8 flex flex-wrap justify-between gap-3">
            <Button variant="secondary" disabled={step === 0} onClick={() => setStep((s) => s - 1)}>
              Back
            </Button>
            {step < steps.length - 1 ? (
              <Button disabled={!canNext()} onClick={() => setStep((s) => s + 1)}>Continue</Button>
            ) : (
              <Button disabled={submitting || !breakdown} onClick={() => void submit()}>
                {submitting ? 'Submitting…' : 'Submit & pay'}
              </Button>
            )}
          </div>
        </Panel>

        <Panel className="h-fit animate-rise delay-1">
          <h3 className="font-display text-xl font-bold">Computation</h3>
          <p className="mt-1 text-sm text-ink/55">
            {loadingCalc ? 'Updating…' : 'Live estimate from server rules'}
          </p>
          <div className="mt-4">
            <MoneyRow label="Basic tax" value={formatPeso(breakdown?.base_tax)} />
            <MoneyRow label="Additional tax" value={formatPeso(breakdown?.additional_tax)} />
            <MoneyRow label="Interest" value={formatPeso(breakdown?.interest_amount)} />
            <MoneyRow label="Delivery fee" value={formatPeso(breakdown?.delivery_fee)} />
            <MoneyRow label="Convenience fee" value={formatPeso(breakdown?.convenience_fee)} />
            <MoneyRow label="Server fee" value={formatPeso(breakdown?.server_fee)} />
            <MoneyRow label="Payment processor fee" value={formatPeso(breakdown?.payment_processor_fee)} />
            <div className="mt-3 flex justify-between text-base font-bold">
              <span>Total due</span>
              <span>{formatPeso(breakdown?.total_due)}</span>
            </div>
          </div>
        </Panel>
      </div>
    </SimpleLayout>
  )
}
