import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Vehicle } from '../types';
import { getVehicle } from '../services/vehicleService';
import { createDossier } from '../services/dossierService';
import { useAuth } from '../context/AuthContext';

/**
 * Page de détail d'un véhicule avec formulaire de dépôt de dossier.
 * Permet au client de soumettre un dossier d'achat ou de location.
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
     * Soumet le dossier d'achat ou de location.
     */
    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setError(null);
        try {
            await createDossier(Number(id), dossierType);
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
                        {/* Photo */}
                        {vehicle.photoUrl ? (
                            <img
                                src={vehicle.photoUrl}
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

                                    <div className="mb-4">
                                        <label className="block text-sm text-gray-700 mb-2">
                                            Type de dossier
                                        </label>
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