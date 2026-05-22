import api from "./api";
import { Dossier } from "../types";

export const createDossier = async (vehicleId: number, type: string): Promise<Dossier> => {
  const response = await api.post("/dossiers", { vehicleId, type });
  return response.data;
};

export const getMyDossiers = async (): Promise<Dossier[]> => {
  const response = await api.get("/dossiers/my-dossiers");
  return response.data;
};

export const getAllDossiers = async (): Promise<Dossier[]> => {
  const response = await api.get("/dossiers");
  return response.data;
};

export const validateDossier = async (
  id: number,
  status: "approved" | "rejected" | "in_progress",
  comment?: string
): Promise<Dossier> => {
  const response = await api.patch(`/dossiers/${id}/validate`, { status, comment });
  return response.data;
};
