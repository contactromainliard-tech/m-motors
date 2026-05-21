import React, { createContext, useContext, useState, useEffect } from "react";
import { User, LoginData, RegisterData } from "../types";
import api from "../services/api";

interface AuthContextType {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  login: (data: LoginData) => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
  logout: () => void;
  isAuthenticated: boolean;
  isAdmin: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const savedToken = localStorage.getItem("token");
    const savedUser = localStorage.getItem("user");
    if (savedToken && savedUser) {
      setToken(savedToken);
      setUser(JSON.parse(savedUser));
    }
    setIsLoading(false);
  }, []);

  const login = async (data: LoginData): Promise<void> => {
    const response = await api.post("/auth/login", data);
    const { token: newToken } = response.data;
    const payload = JSON.parse(atob(newToken.split(".")[1]));
    const userData: User = {
      id: payload.id || 0,
      email: payload.username || payload.email || "",
      firstName: payload.firstName || "",
      lastName: payload.lastName || "",
      isAdmin: payload.roles?.includes("ROLE_ADMIN") || false,
    };
    localStorage.setItem("token", newToken);
    localStorage.setItem("user", JSON.stringify(userData));
    setToken(newToken);
    setUser(userData);
  };

  const register = async (data: RegisterData): Promise<void> => {
    await api.post("/auth/register", data);
  };

  const logout = (): void => {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    setToken(null);
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{
      user, token, isLoading, login, register, logout,
      isAuthenticated: !!token,
      isAdmin: user?.isAdmin || false,
    }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  if (!context) throw new Error("useAuth doit etre utilise dans un AuthProvider");
  return context;
};
