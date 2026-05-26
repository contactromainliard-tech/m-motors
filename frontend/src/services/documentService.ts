import api from "./api";

/**
 * Service gérant les appels API liés aux documents des dossiers.
 */

/**
 * Upload un document pour un dossier.
 */
export const uploadDocument = async (dossierId: number, formData: FormData) => {
    const response = await api.post(`/documents/upload/${dossierId}`, formData, {
        headers: { "Content-Type": "multipart/form-data" },
    });
    return response.data;
};

