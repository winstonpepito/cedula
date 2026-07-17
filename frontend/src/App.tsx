import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { PublicLayout } from './components/Layout'
import { AuthProvider } from './context/AuthContext'
import { ApplyPage } from './pages/ApplyPage'
import { LandingPage } from './pages/LandingPage'
import { MockPayPage } from './pages/MockPayPage'
import { PayPage } from './pages/PayPage'
import { ReceiptPage } from './pages/ReceiptPage'
import { TokenReceiptPage, TrackPage } from './pages/TrackPage'
import { AdminApplicationDetail, AdminApplications } from './pages/admin/AdminApplications'
import { AdminBarangays } from './pages/admin/AdminBarangays'
import { AdminDashboard } from './pages/admin/AdminDashboard'
import { AdminLandingPage } from './pages/admin/AdminLandingPage'
import { AdminLoginPage } from './pages/admin/AdminLoginPage'
import { AdminSettings } from './pages/admin/AdminSettings'
import { AdminShell } from './pages/admin/AdminShell'

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route element={<PublicLayout />}>
            <Route index element={<LandingPage />} />
          </Route>
          <Route path="/apply" element={<ApplyPage />} />
          <Route path="/pay/:tracking" element={<PayPage />} />
          <Route path="/pay/:tracking/mock" element={<MockPayPage />} />
          <Route path="/receipt/:tracking" element={<ReceiptPage />} />
          <Route path="/track" element={<TrackPage />} />
          <Route path="/t/:tracking" element={<TrackPage />} />
          <Route path="/r/:token" element={<TokenReceiptPage />} />

          <Route path="/admin/login" element={<AdminLoginPage />} />
          <Route path="/admin" element={<AdminShell />}>
            <Route index element={<AdminDashboard />} />
            <Route path="applications" element={<AdminApplications />} />
            <Route path="applications/:id" element={<AdminApplicationDetail />} />
            <Route path="barangays" element={<AdminBarangays />} />
            <Route path="homepage" element={<AdminLandingPage />} />
            <Route path="settings" element={<AdminSettings />} />
          </Route>

          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  )
}
