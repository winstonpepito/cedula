import axios, { isAxiosError } from 'axios'

const API_ORIGIN = import.meta.env.VITE_API_URL || ''

export const api = axios.create({
  baseURL: API_ORIGIN ? `${API_ORIGIN}/api` : '/api',
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
})

let csrfReady: Promise<void> | null = null

export async function ensureCsrf() {
  if (!csrfReady) {
    csrfReady = axios
      .get(`${API_ORIGIN || ''}/sanctum/csrf-cookie`, { withCredentials: true })
      .then(() => undefined)
      .catch((err) => {
        csrfReady = null
        throw err
      })
  }
  return csrfReady
}

api.interceptors.request.use(async (config) => {
  const method = (config.method || 'get').toLowerCase()
  if (['post', 'put', 'patch', 'delete'].includes(method)) {
    await ensureCsrf()
  }
  return config
})

export function formatPeso(value: number | string | null | undefined) {
  const n = Number(value || 0)
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(n)
}

export function statusLabel(status: string) {
  return status.replaceAll('_', ' ')
}

/** Download an authenticated staff PDF (session cookie) as a blob. */
export async function downloadAdminPdf(path: string, fallbackName: string) {
  try {
    const response = await api.get(path, {
      responseType: 'blob',
      headers: { Accept: 'application/pdf,application/json' },
    })

    const contentType = String(response.headers['content-type'] || '')
    if (contentType.includes('application/json')) {
      const text = await (response.data as Blob).text()
      let message = 'Unable to download PDF.'
      try {
        message = JSON.parse(text).message || message
      } catch {
        // keep default
      }
      throw new Error(message)
    }

    const disposition = String(response.headers['content-disposition'] || '')
    const match = /filename\*?=(?:UTF-8''|")?([^\";]+)/i.exec(disposition)
    const filename = match ? decodeURIComponent(match[1].replace(/"/g, '')) : fallbackName

    const url = window.URL.createObjectURL(response.data)
    const anchor = document.createElement('a')
    anchor.href = url
    anchor.download = filename
    document.body.appendChild(anchor)
    anchor.click()
    anchor.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    if (isAxiosError(err) && err.response?.data instanceof Blob) {
      try {
        const text = await err.response.data.text()
        const parsed = JSON.parse(text) as { message?: string }
        throw new Error(parsed.message || 'Unable to download PDF.')
      } catch (inner) {
        if (inner instanceof Error && inner.message !== 'Unable to download PDF.') {
          throw inner
        }
      }
    }
    throw err
  }
}
