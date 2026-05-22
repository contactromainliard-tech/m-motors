import api from "./api";

/**
 * Service gérant les appels API liés au profil utilisateur.
 */

/**
 * Récupère le profil de l utilisateur connecté.
 */
export const getProfile = async () => {
    const response = await api.get("/auth/profile");
    return response.data;
};

/**
 * Met à jour les informations personnelles de l utilisateur connecté.
 */
export const updateProfile = async (data: {
    firstName?: string;
    lastName?: string;
    phone?: string;
    address?: string;
    city?: string;
    zipCode?: string;
}) => {
    const response = await api.put("/auth/profile", data);
    return response.data;
};