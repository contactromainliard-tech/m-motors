export interface User {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  phone?: string;
  isAdmin: boolean;
}
export interface Vehicle {
  id: number;
  brand: string;
  model: string;
  year: number;
  kilometrage: number;
  price: number;
  type: "sale" | "rental";
  description?: string;
  photoUrl?: string;
  isAvailable: boolean;
}
export interface Dossier {
  id: number;
  type: "purchase" | "rental";
  status: "pending" | "approved" | "rejected" | "in_progress";
  createdAt: string;
  updatedAt?: string;
  comment?: string;
  client: { id: number; email: string; firstName: string; lastName: string; };
  vehicle: { id: number; brand: string; model: string; type: string; price: number; };
}
export interface VehicleFilters {
  type?: string;
  brand?: string;
  maxPrice?: number;
  maxKilometrage?: number;
}
export interface LoginData { email: string; password: string; }
export interface RegisterData { email: string; password: string; firstName: string; lastName: string; phone?: string; }
