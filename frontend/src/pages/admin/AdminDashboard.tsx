import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { PageTitle, Panel } from '../../components/ui'
import { api, formatPeso, statusLabel } from '../../lib/api'
import { useAuth } from '../../context/AuthContext'

interface DashboardData {
  totals: {
    applications: number
    paid: number
    awaiting_payment: number
    pending_verification: number
    revenue: number
    late_filings: number
  }
  by_mode: Array<{ delivery_mode: string; total: number; amount: number }>
  by_barangay: Array<{ name: string; total: number; amount: number }>
  recent: Array<{
    id: number
    tracking_number: string
    status: string
    total_due: number | string
    applicant_type: string
    first_name?: string
    last_name?: string
    corporation_name?: string
  }>
}

export function AdminDashboard() {
  const { user } = useAuth()
  const [data, setData] = useState<DashboardData | null>(null)

  useEffect(() => {
    if (user?.role !== 'admin') return
    void api.get('/admin/reports/dashboard').then((res) => setData(res.data.data))
  }, [user])

  if (user?.role === 'delivery') {
    return (
      <div>
        <PageTitle title="Delivery queue" subtitle="Open applications to mark out for delivery or delivered." />
        <Panel>
          <Link to="/admin/applications" className="font-semibold text-teal">
            Go to delivery applications →
          </Link>
        </Panel>
      </div>
    )
  }

  if (!data) return <p>Loading reports…</p>

  const cards = [
    ['Applications', data.totals.applications],
    ['Paid', data.totals.paid],
    ['Pending verification', data.totals.pending_verification],
    ['Revenue', formatPeso(data.totals.revenue)],
    ['Awaiting payment', data.totals.awaiting_payment],
    ['Late filings', data.totals.late_filings],
  ] as const

  return (
    <div>
      <PageTitle title="Operations dashboard" subtitle="Live snapshot of cedula applications, payments, and delivery mix." />
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        {cards.map(([label, value]) => (
          <Panel key={label} className="!p-5">
            <div className="text-xs uppercase tracking-[0.16em] text-ink/45">{label}</div>
            <div className="mt-2 font-display text-3xl font-bold text-ink">{value}</div>
          </Panel>
        ))}
      </div>

      <div className="mt-6 grid gap-6 lg:grid-cols-2">
        <Panel>
          <h3 className="font-display text-xl font-bold">By delivery mode</h3>
          <ul className="mt-4 space-y-2 text-sm">
            {data.by_mode.map((row) => (
              <li key={row.delivery_mode} className="flex justify-between border-b border-line/50 py-2">
                <span className="capitalize">{row.delivery_mode.replaceAll('_', ' ')}</span>
                <span>{row.total} · {formatPeso(row.amount || 0)}</span>
              </li>
            ))}
          </ul>
        </Panel>
        <Panel>
          <h3 className="font-display text-xl font-bold">Top barangays</h3>
          <ul className="mt-4 space-y-2 text-sm">
            {data.by_barangay.map((row) => (
              <li key={row.name} className="flex justify-between border-b border-line/50 py-2">
                <span>{row.name}</span>
                <span>{row.total}</span>
              </li>
            ))}
          </ul>
        </Panel>
      </div>

      <Panel className="mt-6">
        <h3 className="font-display text-xl font-bold">Recent applications</h3>
        <div className="mt-4 overflow-x-auto">
          <table className="min-w-full text-left text-sm">
            <thead className="text-ink/50">
              <tr>
                <th className="py-2 pr-4">Tracking</th>
                <th className="py-2 pr-4">Applicant</th>
                <th className="py-2 pr-4">Status</th>
                <th className="py-2">Total</th>
              </tr>
            </thead>
            <tbody>
              {data.recent.map((row) => (
                <tr key={row.id} className="border-t border-line/50">
                  <td className="py-3 pr-4">
                    <Link className="font-semibold text-teal" to={`/admin/applications/${row.id}`}>
                      {row.tracking_number}
                    </Link>
                  </td>
                  <td className="py-3 pr-4">
                    {row.corporation_name || `${row.first_name || ''} ${row.last_name || ''}`.trim()}
                  </td>
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
