import { useEffect, useState, type FormEvent } from 'react'
import { Navigate } from 'react-router-dom'
import { Button, Field, Input, PageTitle, Panel, Select } from '../../components/ui'
import { api } from '../../lib/api'
import { useAuth } from '../../context/AuthContext'

interface StaffAccount {
  id: number
  name: string
  email: string
  role: 'admin' | 'delivery'
  created_at?: string
}

export function AdminUsers() {
  const { user } = useAuth()
  const [rows, setRows] = useState<StaffAccount[]>([])
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [role, setRole] = useState<'delivery' | 'admin'>('delivery')
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')
  const [editingId, setEditingId] = useState<number | null>(null)
  const [editName, setEditName] = useState('')
  const [editEmail, setEditEmail] = useState('')
  const [editPassword, setEditPassword] = useState('')
  const [editRole, setEditRole] = useState<'delivery' | 'admin'>('delivery')

  async function load() {
    const { data } = await api.get('/admin/users')
    setRows(data.data)
  }

  useEffect(() => {
    if (user?.role === 'admin') {
      void load()
    }
  }, [user])

  if (user?.role !== 'admin') {
    return <Navigate to="/admin" replace />
  }

  async function create(e: FormEvent) {
    e.preventDefault()
    setBusy(true)
    setError('')
    setMessage('')
    try {
      await api.post('/admin/users', { name, email, password, role })
      setName('')
      setEmail('')
      setPassword('')
      setRole('delivery')
      setMessage('Staff account created.')
      await load()
    } catch {
      setError('Unable to create user. Check that the email is unique and the password is strong enough.')
    } finally {
      setBusy(false)
    }
  }

  function startEdit(row: StaffAccount) {
    setEditingId(row.id)
    setEditName(row.name)
    setEditEmail(row.email)
    setEditPassword('')
    setEditRole(row.role)
    setError('')
    setMessage('')
  }

  async function saveEdit(e: FormEvent) {
    e.preventDefault()
    if (editingId == null) return
    setBusy(true)
    setError('')
    setMessage('')
    try {
      await api.put(`/admin/users/${editingId}`, {
        name: editName,
        email: editEmail,
        role: editRole,
        password: editPassword || undefined,
      })
      setEditingId(null)
      setMessage('Staff account updated.')
      await load()
    } catch {
      setError('Unable to update user.')
    } finally {
      setBusy(false)
    }
  }

  async function remove(row: StaffAccount) {
    if (!window.confirm(`Delete staff account "${row.name}" (${row.email})?`)) return
    setError('')
    setMessage('')
    try {
      await api.delete(`/admin/users/${row.id}`)
      setMessage('Staff account deleted.')
      if (editingId === row.id) setEditingId(null)
      await load()
    } catch {
      setError('Unable to delete user. You cannot delete yourself or the only admin.')
    }
  }

  return (
    <div>
      <PageTitle
        title="Staff users"
        subtitle="Create and manage admin and delivery accounts. Delivery users can open receipts and update delivery status."
      />

      <Panel className="mb-6">
        <h3 className="font-display text-xl font-bold">Add staff user</h3>
        <form className="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-5" onSubmit={(e) => void create(e)}>
          <Field label="Name">
            <Input value={name} onChange={(e) => setName(e.target.value)} required />
          </Field>
          <Field label="Email">
            <Input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
          </Field>
          <Field label="Password">
            <Input type="password" value={password} onChange={(e) => setPassword(e.target.value)} required minLength={8} />
          </Field>
          <Field label="Role">
            <Select value={role} onChange={(e) => setRole(e.target.value as 'delivery' | 'admin')}>
              <option value="delivery">Delivery</option>
              <option value="admin">Admin</option>
            </Select>
          </Field>
          <div className="flex items-end">
            <Button className="w-full" disabled={busy}>{busy ? 'Saving…' : 'Add user'}</Button>
          </div>
        </form>
      </Panel>

      {message ? <p className="mb-4 text-sm text-teal-deep">{message}</p> : null}
      {error ? <p className="mb-4 text-sm text-accent">{error}</p> : null}

      {editingId != null ? (
        <Panel className="mb-6">
          <h3 className="font-display text-xl font-bold">Edit staff user</h3>
          <form className="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-5" onSubmit={(e) => void saveEdit(e)}>
            <Field label="Name">
              <Input value={editName} onChange={(e) => setEditName(e.target.value)} required />
            </Field>
            <Field label="Email">
              <Input type="email" value={editEmail} onChange={(e) => setEditEmail(e.target.value)} required />
            </Field>
            <Field label="New password" hint="Leave blank to keep current password.">
              <Input type="password" value={editPassword} onChange={(e) => setEditPassword(e.target.value)} minLength={8} />
            </Field>
            <Field label="Role">
              <Select value={editRole} onChange={(e) => setEditRole(e.target.value as 'delivery' | 'admin')}>
                <option value="delivery">Delivery</option>
                <option value="admin">Admin</option>
              </Select>
            </Field>
            <div className="flex items-end gap-2">
              <Button className="w-full" disabled={busy}>Save</Button>
              <Button type="button" variant="secondary" onClick={() => setEditingId(null)}>Cancel</Button>
            </div>
          </form>
        </Panel>
      ) : null}

      <Panel>
        <div className="overflow-x-auto">
          <table className="min-w-full text-left text-sm">
            <thead className="text-ink/50">
              <tr>
                <th className="py-2 pr-4">Name</th>
                <th className="py-2 pr-4">Email</th>
                <th className="py-2 pr-4">Role</th>
                <th className="py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.id} className="border-t border-line/50">
                  <td className="py-3 pr-4 font-semibold">{row.name}</td>
                  <td className="py-3 pr-4">{row.email}</td>
                  <td className="py-3 pr-4 capitalize">{row.role}</td>
                  <td className="py-3 text-right">
                    <div className="flex justify-end gap-2">
                      <Button variant="secondary" onClick={() => startEdit(row)}>Edit</Button>
                      <Button variant="danger" onClick={() => void remove(row)}>Delete</Button>
                    </div>
                  </td>
                </tr>
              ))}
              {!rows.length ? (
                <tr>
                  <td colSpan={4} className="py-6 text-ink/50">No staff users found.</td>
                </tr>
              ) : null}
            </tbody>
          </table>
        </div>
      </Panel>
    </div>
  )
}
