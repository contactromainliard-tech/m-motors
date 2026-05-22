import api from "./api";
import { Vehicle, VehicleFilters } from "../types";

/**
 * Service gérant les appels API liés aux véhicules.
 */

/**
 * Récupère la liste des véhicules avec filtres optionnels.
 */
export const getVehicles = async (filters?: VehicleFilters): Promise<Vehicle[]> => {
  const params = new URLSearchParams();
  if (filters?.type) params.append("type", filters.type);
  if (filters?.brand) params.append("brand", filters.brand);
  if (filters?.maxPrice) params.append("maxPrice", filters.maxPrice.toString());
  if (filters?.maxKilometrage) params.append("maxKilometrage", filters.maxKilometrage.toString());
  const response = await api.get(`/vehicles?${params.toString()}`);
  return response.data;
};

/**
 * Récupère un véhicule par son identifiant.
 */
export const getVehicle = async (id: number): Promise<Vehicle> => {
  const response = await api.get(`/vehicles/${id}`);
  return response.data;
};

/**
 * Crée un nouveau véhicule (admin uniquement).
 */
export const createVehicle = async (data: Partial<Vehicle>): Promise<Vehicle> => {
  const response = await api.post("/vehicles", data);
  return response.data;
};

/**
 * Met à jour un véhicule existant (admin uniquement).
 */
export const updateVehicle = async (id: number, data: Partial<Vehicle>): Promise<Vehicle> => {
  const response = await api.put(`/vehicles/${id}`, data);
  return response.data;
};

/**
 * Bascule le type d'un véhicule entre location et vente (admin uniquement).
 */
export const toggleVehicleType = async (id: number): Promise<Vehicle> => {
  const response = await api.patch(`/vehicles/${id}/toggle-type`);
  return response.data.vehicle;
};

/**
 * Upload une photo pour un véhicule (admin uniquement).
 */
export const uploadVehiclePhoto = async (id: number, formData: FormData): Promise<Vehicle> => {
  const response = await api.post(`/vehicles/${id}/upload-photo`, formData, {
    headers: { "Content-Type": "multipart/form-data" },
  });
  return response.data.vehicle;
};