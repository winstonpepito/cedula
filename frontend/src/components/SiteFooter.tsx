type FooterVariant = 'light' | 'dark' | 'admin'

export function SiteFooter({ variant = 'light' }: { variant?: FooterVariant }) {
  const styles = {
    light: 'border-t border-line/70 bg-white/70 text-ink/55',
    dark: 'border-t border-white/10 bg-black/25 text-white/65 backdrop-blur',
    admin: 'border-t border-line/70 bg-white/60 text-ink/50',
  }[variant]

  return (
    <footer className={`mt-auto ${styles}`}>
      <div className="mx-auto max-w-7xl px-5 py-5 text-center text-xs md:px-8 md:text-sm">
        © 2026 Hon. Winston Pepito. All rights reserved.
      </div>
    </footer>
  )
}
