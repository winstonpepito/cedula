import type { ButtonHTMLAttributes, InputHTMLAttributes, ReactNode, SelectHTMLAttributes } from 'react'

export function Button({
  variant = 'primary',
  className = '',
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { variant?: 'primary' | 'secondary' | 'ghost' | 'danger' }) {
  const styles = {
    primary: 'bg-teal text-white hover:bg-teal-deep shadow-sm',
    secondary: 'bg-white text-ink border border-line hover:bg-mist',
    ghost: 'bg-transparent text-teal hover:bg-teal-soft',
    danger: 'bg-accent text-white hover:opacity-90',
  }[variant]

  return (
    <button
      className={`inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 ${styles} ${className}`}
      {...props}
    />
  )
}

export function Field({
  label,
  children,
  hint,
}: {
  label: string
  children: ReactNode
  hint?: string
}) {
  return (
    <label className="block space-y-1.5">
      <span className="text-sm font-semibold text-ink/80">{label}</span>
      {children}
      {hint ? <span className="block text-xs text-ink/50">{hint}</span> : null}
    </label>
  )
}

export function Input(props: InputHTMLAttributes<HTMLInputElement>) {
  return (
    <input
      {...props}
      className={`w-full rounded-xl border border-line bg-white px-3.5 py-2.5 text-sm outline-none ring-teal/30 focus:ring-2 ${props.className || ''}`}
    />
  )
}

export function Select(props: SelectHTMLAttributes<HTMLSelectElement>) {
  return (
    <select
      {...props}
      className={`w-full rounded-xl border border-line bg-white px-3.5 py-2.5 text-sm outline-none ring-teal/30 focus:ring-2 ${props.className || ''}`}
    />
  )
}

export function Panel({ children, className = '' }: { children: ReactNode; className?: string }) {
  return (
    <div className={`rounded-3xl border border-line/80 bg-white/90 p-6 shadow-[0_20px_60px_rgba(11,36,39,0.06)] md:p-8 ${className}`}>
      {children}
    </div>
  )
}

export function PageTitle({ title, subtitle }: { title: string; subtitle?: string }) {
  return (
    <div className="mb-8 animate-rise">
      <h1 className="font-display text-3xl font-bold text-ink md:text-4xl">{title}</h1>
      {subtitle ? <p className="mt-2 max-w-2xl text-ink/65">{subtitle}</p> : null}
    </div>
  )
}

export function MoneyRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex items-center justify-between gap-4 border-b border-line/60 py-2 text-sm">
      <span className="text-ink/65">{label}</span>
      <span className="font-semibold tabular-nums">{value}</span>
    </div>
  )
}
