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

/**
 * Récupère la liste des documents d un dossier.
 */
export const getDocumentsByDossier = async (dossierId: number) => {
    const response = await api.get(`/documents/dossier/${dossierId}`);
    return response.data;
};