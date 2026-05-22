import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Dossier } from '../types';
import { getMyDossiers } from '../services/dossierService';
import { useAuth } from '../context/AuthContext';

/**
 * Page de suivi des dossiers du client connecté.
 * Affiche l'état d'avancement de chaque dossier soumis.
 */
const MyDossiersPage: React.FC = () => {
    const { isAuthenticated, logout } = useAuth();
    const navigate = useNavigate();

    const [dossiers, setDossiers] = useState<Dossier[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!isAuthenticated) {
            navigate('/login');
            return;
        }
        loadDossiers();
    }, []);

    /**
     * Charge les dossiers du client connecté depuis l'API.
     */
    const loadDossiers = async () => {
        try {
            const data = await getMyDossiers();
            setDossiers(data);
        } catch (err) {
            setError('Impossible de charger vos dossiers.');
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * Retourne les classes Tailwind selon le statut du dossier.
     */
    const getStatusStyle = (status: string): string => {
        switch (status) {
            case 'approved': return 'bg-green-100 text-green-700';
            case 'rejected': return 'bg-red-100 text-red-700';
            case 'in_progress': return 'bg-blue-100 text-blue-700';
            default: return 'bg-yellow-100 text-yellow-700';
        }
    };

    /**
     * Traduit le statut du dossier en français.
     */
    const getStatusLabel = (status: string): string => {
        switch (status) {
            case 'approved': return 'Valide';
            case 'rejected': return 'Refuse';
            case 'in_progress': return 'En cours de traitement';
            default: return 'En attente';
        }
    };

    return (
        <div className="min-h-screen bg-gray-100">
            {/* Navigation */}
            <nav className="bg-primary text-white px-8 py-4 flex justify-between items-center">
                <h1
                    className="text-2xl font-bold cursor-pointer"
                    onClick={() => navigate('/')}
                >
                    M-Motors
                </h1>
                <div className="flex gap-3">
                    <button
                        onClick={() => navigate('/')}
                        className="px-4 py-2 text-sm border border-white rounded hover:bg-white hover:text-primary transition"
                    >
                        Catalogue
                    </button>
                    <button
                        onClick={logout}
                        className="px-4 py-2 text-sm border border-white rounded hover:bg-white hover:text-primary transition"
                    >
                        Déconnexion
                    </button>
                </div>
            </nav>

            <div className="max-w-4xl mx-auto p-8">
                <h2 className="text-2xl font-bold text-primary mb-6">Mes dossiers</h2>

                {isLoading && (
                    <p className="text-center text-gray-500">Chargement...</p>
                )}

                {error && (
                    <p className="text-center text-red-500">{error}</p>
                )}

                {!isLoading && !error && dossiers.length === 0 && (
                    <div className="bg-white rounded-lg shadow-sm p-8 text-center">
                        <p className="text-gray-500 mb-4">Vous n'avez pas encore de dossier.</p>
                        <button
                            onClick={() => navigate('/')}
                            className="px-6 py-2 bg-secondary text-white rounded hover:opacity-90 transition"
                        >
                            Parcourir le catalogue
                        </button>
                    </div>
                )}

                <div className="space-y-4">
                    {dossiers.map((dossier) => (
                        <div key={dossier.id} className="bg-white rounded-lg shadow-sm p-6">
                            <div className="flex justify-between items-start mb-3">
                                <div>
                                    <h3 className="font-semibold text-primary text-lg">
                                        {dossier.vehicle.brand} {dossier.vehicle.model}
                                    </h3>
                                    <p className="text-gray-500 text-sm">
                                        Dossier #{dossier.id} ·{' '}
                                        {dossier.type === 'purchase' ? 'Achat' : 'Location LLD'}
                                    </p>
                                </div>
                                <span className={`px-3 py-1 rounded-full text-sm font-medium ${getStatusStyle(dossier.status)}`}>
                                    {getStatusLabel(dossier.status)}
                                </span>
                            </div>

                            <div className="text-sm text-gray-500 border-t border-gray-100 pt-3 mt-3">
                                <p>
                                    Soumis le{' '}
                                    {new Date(dossier.createdAt).toLocaleDateString('fr-FR', {
                                        day: 'numeric',
                                        month: 'long',
                                        year: 'numeric',
                                    })}
                                </p>
                                {dossier.comment && (
                                    <p className="mt-2 text-gray-600 bg-gray-50 p-3 rounded">
                                        <span className="font-medium">Commentaire :</span> {dossier.comment}
                                    </p>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
};

export default MyDossiersPage;