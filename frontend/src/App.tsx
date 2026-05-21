import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import VehiclesPage from './pages/VehiclesPage';
import VehicleDetailPage from './pages/VehicleDetailPage';
import MyDossiersPage from './pages/MyDossiersPage';
import AdminPage from './pages/AdminPage';

/**
 * Composant de route protégée - redirige vers /login si non authentifié.
 */
const PrivateRoute: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { isAuthenticated, isLoading } = useAuth();
  if (isLoading) return <div className="min-h-screen flex items-center justify-center">Chargement...</div>;
  return isAuthenticated ? <>{children}</> : <Navigate to="/login" />;
};

/**
 * Composant de route admin - redirige vers / si non administrateur.
 */
const AdminRoute: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { isAuthenticated, isAdmin, isLoading } = useAuth();
  if (isLoading) return <div className="min-h-screen flex items-center justify-center">Chargement...</div>;
  if (!isAuthenticated) return <Navigate to="/login" />;
  return isAdmin ? <>{children}</> : <Navigate to="/" />;
};

/**
 * Configuration des routes de l'application M-Motors.
 */
const AppRoutes: React.FC = () => {
  return (
    <Routes>
      <Route path="/" element={<VehiclesPage />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />
      <Route path="/vehicles/:id" element={
        <PrivateRoute>
          <VehicleDetailPage />
        </PrivateRoute>
      } />
      <Route path="/my-dossiers" element={
        <PrivateRoute>
          <MyDossiersPage />
        </PrivateRoute>
      } />
      <Route path="/admin" element={
        <AdminRoute>
          <AdminPage />
        </AdminRoute>
      } />
      <Route path="*" element={<Navigate to="/" />} />
    </Routes>
  );
};

/**
 * Composant racine de l'application M-Motors.
 * Fournit le contexte d'authentification et le router.
 */
const App: React.FC = () => {
  return (
    <BrowserRouter>
      <AuthProvider>
        <AppRoutes />
      </AuthProvider>
    </BrowserRouter>
  );
};

export default App;