import axios from 'axios'

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
