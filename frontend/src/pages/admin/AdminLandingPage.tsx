import { useEffect, useState, type FormEvent } from 'react'
import { Button, Field, Input, PageTitle, Panel, Select } from '../../components/ui'
import { api } from '../../lib/api'

function resolveImageUrl(url: string | null) {
  if (!url) return null
  if (url.startsWith('http')) {
    try {
      const parsed = new URL(url)
      return parsed.pathname + parsed.search
    } catch {
      return url
    }
  }
  return url
}

export function AdminLandingPage() {
  const [headline, setHeadline] = useState('')
  const [introText, setIntroText] = useState('')
  const [imagePosition, setImagePosition] = useState<'before' | 'after'>('after')
  const [imageUrl, setImageUrl] = useState<string | null>(null)
  const [file, setFile] = useState<File | null>(null)
  const [removeImage, setRemoveImage] = useState(false)
  const [saved, setSaved] = useState(false)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')

  async function load() {
    const { data } = await api.get('/admin/landing')
    setHeadline(data.data.headline || '')
    setIntroText(data.data.intro_text || '')
    setImagePosition(data.data.image_position === 'before' ? 'before' : 'after')
    setImageUrl(resolveImageUrl(data.data.image_url))
    setRemoveImage(false)
    setFile(null)
  }

  useEffect(() => {
    void load()
  }, [])

  async function save(e: FormEvent) {
    e.preventDefault()
    setBusy(true)
    setError('')
    setSaved(false)
    try {
      const body = new FormData()
      body.append('headline', headline)
      body.append('intro_text', introText)
      body.append('image_position', imagePosition)
      body.append('remove_image', removeImage ? '1' : '0')
      if (file) body.append('image', file)

      const { data } = await api.post('/admin/landing', body)
      setHeadline(data.data.headline)
      setIntroText(data.data.intro_text)
      setImagePosition(data.data.image_position)
      setImageUrl(resolveImageUrl(data.data.image_url))
      setFile(null)
      setRemoveImage(false)
      setSaved(true)
      setTimeout(() => setSaved(false), 2000)
    } catch {
      setError('Unable to save homepage content.')
    } finally {
      setBusy(false)
    }
  }

  return (
    <div>
      <PageTitle
        title="Homepage content"
        subtitle="Edit the welcome headline and introductory text. Upload an image and place it before or after the main text."
      />
      <Panel>
        <form className="space-y-5" onSubmit={(e) => void save(e)}>
          <Field label="Headline">
            <Input value={headline} onChange={(e) => setHeadline(e.target.value)} required />
          </Field>
          <Field label="Introductory text" hint="Shown as the main welcome copy on the landing page.">
            <textarea
              value={introText}
              onChange={(e) => setIntroText(e.target.value)}
              required
              className="min-h-36 w-full rounded-xl border border-line bg-white px-3.5 py-2.5 text-sm outline-none ring-teal/30 focus:ring-2"
            />
          </Field>
          <Field label="Image placement">
            <Select
              value={imagePosition}
              onChange={(e) => setImagePosition(e.target.value as 'before' | 'after')}
            >
              <option value="before">Before the main text</option>
              <option value="after">After the main text</option>
            </Select>
          </Field>
          <Field label="Upload image" hint="JPG/PNG up to 5MB. Optional.">
            <input
              type="file"
              accept="image/*"
              onChange={(e) => {
                setFile(e.target.files?.[0] || null)
                setRemoveImage(false)
              }}
              className="block w-full text-sm"
            />
          </Field>

          {imageUrl ? (
            <div className="space-y-3">
              {!removeImage ? (
                <img src={imageUrl} alt="Current landing" className="max-h-56 rounded-2xl object-cover" />
              ) : (
                <p className="text-sm text-ink/55">Image will be removed on save.</p>
              )}
              <label className="flex items-center gap-2 text-sm text-ink/70">
                <input
                  type="checkbox"
                  checked={removeImage}
                  onChange={(e) => setRemoveImage(e.target.checked)}
                />
                Remove current image
              </label>
            </div>
          ) : null}

          {error ? <p className="text-sm text-accent">{error}</p> : null}
          <div className="flex items-center gap-3">
            <Button type="submit" disabled={busy}>{busy ? 'Saving…' : 'Save homepage'}</Button>
            {saved ? <span className="text-sm text-teal-deep">Saved.</span> : null}
          </div>
        </form>
      </Panel>
    </div>
  )
}
