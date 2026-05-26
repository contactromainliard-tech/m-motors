import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Vehicle } from '../types';
import { getVehicle } from '../services/vehicleService';
import { createDossier } from '../services/dossierService';
import { uploadDocument } from '../services/documentService';
import { useAuth } from '../context/AuthContext';

/**
 * Page de détail d'un véhicule avec formulaire de dépôt de dossier.
 * Permet au client de soumettre un dossier avec ses documents justificatifs.
 */
const VehicleDetailPage: React.FC = () => {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { isAuthenticated } = useAuth();

    const [vehicle, setVehicle] = useState<Vehicle | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState(false);
    const [dossierType, setDossierType] = useState<'purchase' | 'rental'>('purchase');

    // Documents à joindre au dossier
    const [documents, setDocuments] = useState<{ type: string; file: File | null }[]>([
        { type: 'cni', file: null },
        { type: 'justificatif_domicile', file: null },
        { type: 'fiche_paie', file: null },
        { type: 'rib', file: null },
    ]);

    useEffect(() => {
        if (!isAuthenticated) {
            navigate('/login');
            return;
        }
        loadVehicle();
    }, [id]);

    /**
     * Charge les détails du véhicule depuis l'API.
     */
    const loadVehicle = async () => {
        try {
            const data = await getVehicle(Number(id));
            setVehicle(data);
            setDossierType(data.type === 'rental' ? 'rental' : 'purchase');
        } catch (err) {
            setError('Véhicule introuvable.');
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * Met à jour le fichier sélectionné pour un type de document.
     */
    const handleFileChange = (index: number, file: File | null) => {
        const updated = [...documents];
        updated[index].file = file;
        setDocuments(updated);
    };

    /**
     * Retourne le label français pour un type de document.
     */
    const getDocumentLabel = (type: string): string => {
        switch (type) {
            case 'cni': return 'Carte nationale d\'identité';
            case 'justificatif_domicile': return 'Justificatif de domicile';
            case 'fiche_paie': return 'Fiche de paie';
            case 'rib': return 'RIB';
            default: return type;
        }
    };

    /**
     * Soumet le dossier avec les documents joints.
     */
    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        // Validation taille des fichiers (1 Mo max en prod)
        const maxSize = 1024 * 1024; // 1 Mo
        const oversizedFiles = documents.filter(doc => doc.file && doc.file.size > maxSize);
        if (oversizedFiles.length > 0) {
            setError('Chaque document doit faire moins de 1 Mo.');
            setIsSubmitting(false);
            return;
        }
        setError(null);

        try {
            // Création du dossier
            const dossier = await createDossier(Number(id), dossierType);

            // Upload des documents sélectionnés
            const uploadPromises = documents
                .filter(doc => doc.file !== null)
                .map(doc => {
                    const formData = new FormData();
                    formData.append('document', doc.file!);
                    formData.append('type', doc.type);
                    return uploadDocument(dossier.id, formData);
                });

            await Promise.all(uploadPromises);
            setSuccess(true);
        } catch (err: any) {
            setError(err.response?.data?.message || 'Erreur lors de la soumission du dossier.');
        } finally {
            setIsSubmitting(false);
        }
    };

    if (isLoading) {
        return (
            <div className="min-h-screen bg-gray-100 flex items-center justify-center">
                <p className="text-gray-500">Chargement...</p>
            </div>
        );
    }

    if (error && !vehicle) {
        return (
            <div className="min-h-screen bg-gray-100 flex items-center justify-center">
                <div className="text-center">
                    <p className="text-red-500 mb-4">{error}</p>
                    <button onClick={() => navigate('/')} className="text-primary underline">
                        Retour aux véhicules
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-100">
            {/* Navigation */}
            <nav className="bg-primary text-white px-8 py-4 flex justify-between items-center">
                <h1 className="text-2xl font-bold cursor-pointer" onClick={() => navigate('/')}>
                    M-Motors
                </h1>
                <button
                    onClick={() => navigate('/')}
                    className="px-4 py-2 text-sm border border-white rounded hover:bg-white hover:text-primary transition"
                >
                    Retour aux véhicules
                </button>
            </nav>

            <div className="max-w-4xl mx-auto p-8">
                {vehicle && (
                    <div className="bg-white rounded-lg shadow-sm overflow-hidden">
                        {/* Photo du véhicule */}
                        {vehicle.photoUrl ? (
                            <img
                                src={`http://localhost:8000${vehicle.photoUrl}`}
                                alt={`${vehicle.brand} ${vehicle.model}`}
                                className="w-full h-64 object-cover"
                            />
                        ) : (
                            <div className="w-full h-64 bg-gray-100 flex items-center justify-center text-gray-400">
                                Pas de photo disponible
                            </div>
                        )}

                        <div className="p-6">
                            <div className="flex justify-between items-start mb-4">
                                <div>
                                    <span className="inline-block bg-primary text-white text-xs px-2 py-1 rounded mb-2">
                                        {vehicle.type === 'sale' ? 'Achat' : 'Location LLD'}
                                    </span>
                                    <h2 className="text-2xl font-bold text-primary">
                                        {vehicle.brand} {vehicle.model}
                                    </h2>
                                    <p className="text-gray-500">
                                        {vehicle.year} · {vehicle.kilometrage.toLocaleString()} km
                                    </p>
                                </div>
                                <div className="text-right">
                                    <p className="text-3xl font-bold text-secondary">
                                        {vehicle.price.toLocaleString()} €
                                    </p>
                                    {vehicle.type === 'rental' && (
                                        <p className="text-gray-400 text-sm">par mois</p>
                                    )}
                                </div>
                            </div>

                            {vehicle.description && (
                                <p className="text-gray-600 mb-6 border-t border-gray-100 pt-4">
                                    {vehicle.description}
                                </p>
                            )}

                            {/* Formulaire de dossier */}
                            {success ? (
                                <div className="bg-green-50 border border-green-200 rounded p-6 text-center">
                                    <p className="text-green-700 font-semibold text-lg mb-2">
                                        Dossier soumis avec succès !
                                    </p>
                                    <p className="text-green-600 text-sm mb-4">
                                        Vous pouvez suivre l'avancement de votre dossier dans votre espace client.
                                    </p>
                                    <button
                                        onClick={() => navigate('/my-dossiers')}
                                        className="px-6 py-2 bg-primary text-white rounded hover:opacity-90 transition"
                                    >
                                        Voir mes dossiers
                                    </button>
                                </div>
                            ) : (
                                <form onSubmit={handleSubmit} className="border-t border-gray-100 pt-6">
                                    <h3 className="font-semibold text-primary text-lg mb-4">
                                        Déposer un dossier
                                    </h3>

                                    {error && (
                                        <div className="bg-red-50 text-red-600 p-3 rounded mb-4 text-sm">
                                            {error}
                                        </div>
                                    )}

                                    {/* Type de dossier */}
                                    <div className="mb-6">
                                        <label className="block text-sm text-gray-700 mb-2">Type de dossier</label>
                                        <div className="flex gap-4">
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    value="purchase"
                                                    checked={dossierType === 'purchase'}
                                                    onChange={() => setDossierType('purchase')}
                                                    className="accent-primary"
                                                />
                                                <span className="text-sm">Achat</span>
                                            </label>
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    value="rental"
                                                    checked={dossierType === 'rental'}
                                                    onChange={() => setDossierType('rental')}
                                                    className="accent-primary"
                                                />
                                                <span className="text-sm">Location longue durée</span>
                                            </label>
                                        </div>
                                    </div>

                                    {/* Documents justificatifs */}
                                    <div className="mb-6">
                                        <h4 className="text-sm font-medium text-gray-700 mb-3">
                                            Documents justificatifs
                                            <span className="text-gray-400 font-normal ml-2">(PDF, JPG ou PNG, 1 Mo max)</span>
                                        </h4>
                                        <div className="space-y-3">
                                            {documents.map((doc, index) => (
                                                <div key={doc.type} className="flex items-center gap-4">
                                                    <label className="w-56 text-sm text-gray-600 shrink-0">
                                                        {getDocumentLabel(doc.type)}
                                                    </label>
                                                    <input
                                                        type="file"
                                                        accept=".pdf,.jpg,.jpeg,.png"
                                                        onChange={(e) => handleFileChange(index, e.target.files?.[0] || null)}
                                                        className="flex-1 border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-primary"
                                                    />
                                                    {doc.file && (
                                                        <span className="text-green-500 text-xs shrink-0">
                                                            Sélectionné
                                                        </span>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={isSubmitting}
                                        className="w-full py-3 bg-secondary text-white rounded hover:opacity-90 transition disabled:opacity-50 font-semibold"
                                    >
                                        {isSubmitting ? 'Envoi en cours...' : 'Soumettre mon dossier'}
                                    </button>
                                </form>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default VehicleDetailPage;