import { Link } from 'react-router-dom'
import { useEffect, useState } from 'react'
import { api } from '../lib/api'

interface LandingData {
  headline: string
  intro_text: string
  image_url: string | null
  image_position: 'before' | 'after'
}

const defaults: LandingData = {
  headline: 'Apply for your Community Tax Certificate online',
  intro_text: 'Transparent computation, secure payment, and trackable delivery — built for residents and businesses.',
  image_url: null,
  image_position: 'after',
}

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

export function LandingPage() {
  const [content, setContent] = useState<LandingData>(defaults)

  useEffect(() => {
    void api.get('/landing').then((res) => {
      setContent({
        headline: res.data.data.headline || defaults.headline,
        intro_text: res.data.data.intro_text || defaults.intro_text,
        image_url: resolveImageUrl(res.data.data.image_url),
        image_position: res.data.data.image_position === 'before' ? 'before' : 'after',
      })
    }).catch(() => undefined)
  }, [])

  const image = content.image_url ? (
    <img
      src={content.image_url}
      alt=""
      className="animate-rise delay-1 mt-6 w-full max-w-lg rounded-2xl object-cover shadow-2xl ring-1 ring-white/20"
    />
  ) : null

  return (
    <section className="relative min-h-screen overflow-hidden">
      <div
        className="absolute inset-0 bg-cover bg-center"
        style={{
          backgroundImage:
            'linear-gradient(120deg, rgba(9,40,43,0.82), rgba(13,115,119,0.55)), url("https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=2000&q=80")',
        }}
      />
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(215,239,239,0.18),transparent_40%),radial-gradient(circle_at_80%_70%,rgba(196,92,38,0.18),transparent_35%)]" />

      <div className="relative z-10 mx-auto flex min-h-screen max-w-6xl flex-col justify-end px-5 pb-16 pt-32 md:justify-center md:px-8 md:pb-24">
        <p className="animate-rise font-display text-5xl font-bold tracking-tight text-white md:text-7xl lg:text-8xl">
          eCedula
        </p>
        <h1 className="animate-rise delay-1 mt-5 max-w-xl text-2xl font-semibold text-white md:text-3xl">
          {content.headline}
        </h1>

        {content.image_position === 'before' ? image : null}

        <p className="animate-rise delay-2 mt-4 max-w-lg whitespace-pre-line text-base text-white/80 md:text-lg">
          {content.intro_text}
        </p>

        {content.image_position === 'after' ? image : null}

        <div className="animate-rise delay-3 mt-8 flex flex-wrap gap-3">
          <Link
            to="/apply"
            className="rounded-2xl bg-white px-6 py-3.5 text-sm font-bold text-teal-deep shadow-lg transition hover:-translate-y-0.5"
          >
            Apply for Cedula
          </Link>
          <Link
            to="/track"
            className="rounded-2xl border border-white/40 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/10"
          >
            Track application
          </Link>
        </div>
      </div>
    </section>
  )
}
