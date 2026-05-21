import api from "./api";
import { Vehicle, VehicleFilters } from "../types";

export const getVehicles = async (filters?: VehicleFilters): Promise<Vehicle[]> => {
  const params = new URLSearchParams();
  if (filters?.type) params.append("type", filters.type);
  if (filters?.brand) params.append("brand", filters.brand);
  if (filters?.maxPrice) params.append("maxPrice", filters.maxPrice.toString());
  const response = await api.get(`/vehicles?${params.toString()}`);
  return response.data;
};

export const getVehicle = async (id: number): Promise<Vehicle> => {
  const response = await api.get(`/vehicles/${id}`);
  return response.data;
};

export const createVehicle = async (data: Partial<Vehicle>): Promise<Vehicle> => {
  const response = await api.post("/vehicles", data);
  return response.data;
};

export const updateVehicle = async (id: number, data: Partial<Vehicle>): Promise<Vehicle> => {
  const response = await api.put(`/vehicles/${id}`, data);
  return response.data;
};

export const toggleVehicleType = async (id: number): Promise<Vehicle> => {
  const response = await api.patch(`/vehicles/${id}/toggle-type`);
  return response.data.vehicle;
};
