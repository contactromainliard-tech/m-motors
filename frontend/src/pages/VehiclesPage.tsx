import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Vehicle, VehicleFilters } from '../types';
import { getVehicles } from '../services/vehicleService';
import { useAuth } from '../context/AuthContext';

/**
 * Page principale de recherche et affichage des véhicules.
 * Accessible publiquement, permet de filtrer par type, marque et budget.
 */
const VehiclesPage: React.FC = () => {
    const { isAuthenticated, isAdmin, logout } = useAuth();
    const navigate = useNavigate();

    const [vehicles, setVehicles] = useState<Vehicle[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [filters, setFilters] = useState<VehicleFilters>({});

    useEffect(() => {
        loadVehicles();
    }, []);

    /**
     * Charge les véhicules depuis l'API avec les filtres appliqués.
     */
    const loadVehicles = async (appliedFilters?: VehicleFilters) => {
        setIsLoading(true);
        setError(null);
        try {
            const data = await getVehicles(appliedFilters);
            setVehicles(data);
        } catch (err) {
            setError('Impossible de charger les véhicules.');
        } finally {
            setIsLoading(false);
        }
    };

    const handleFilterChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        setFilters({ ...filters, [e.target.name]: e.target.value });
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        loadVehicles(filters);
    };

    const handleReset = () => {
        setFilters({});
        loadVehicles();
    };

    return (
        <div className="min-h-screen bg-gray-100">
            {/* Navigation */}
            <nav className="bg-primary text-white px-8 py-4 flex justify-between items-center">
                <h1 className="text-2xl font-bold">M-Motors</h1>
                <div className="flex gap-3">
                    {isAuthenticated ? (
                        <>
                            {!isAdmin && (
                                <button
                                    onClick={() => navigate('/my-dossiers')}
                                    className="px-4 py-2 text-sm border border-white rounded hover:bg-white hover:text-primary transition"
                                >
                                    Mes dossiers
                                </button>
                            )}
                            {isAdmin && (
                                <button
                                    onClick={() => navigate('/admin')}
                                    className="px-4 py-2 text-sm bg-accent text-white rounded hover:opacity-90 transition"
                                >
                                    Back-office
                                </button>
                            )}
                            <button
                                onClick={logout}
                                className="px-4 py-2 text-sm border border-white rounded hover:bg-white hover:text-primary transition"
                            >
                                Déconnexion
                            </button>
                        </>
                    ) : (
                        <>
                            <button
                                onClick={() => navigate('/login')}
                                className="px-4 py-2 text-sm border border-white rounded hover:bg-white hover:text-primary transition"
                            >
                                Connexion
                            </button>
                            <button
                                onClick={() => navigate('/register')}
                                className="px-4 py-2 text-sm bg-secondary text-white rounded hover:opacity-90 transition"
                            >
                                S'inscrire
                            </button>
                        </>
                    )}
                </div>
            </nav>

            {/* Filtres */}
            <div className="bg-white px-8 py-4 border-b border-gray-200">
                <h2 className="text-lg font-semibold text-primary mb-3">Rechercher un véhicule</h2>
                <form onSubmit={handleSearch} className="flex flex-wrap gap-3 items-center">
                    <select
                        name="type"
                        value={filters.type || ''}
                        onChange={handleFilterChange}
                        className="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                    >
                        <option value="">Achat & Location</option>
                        <option value="sale">Achat</option>
                        <option value="rental">Location longue durée</option>
                    </select>

                    <input
                        type="text"
                        name="brand"
                        placeholder="Marque (ex: Renault)"
                        value={filters.brand || ''}
                        onChange={handleFilterChange}
                        className="border border-gray-300 rounded px-3 py-2 text-sm w-44 focus:outline-none focus:border-primary"
                    />

                    <input
                        type="number"
                        name="maxPrice"
                        placeholder="Budget max (€)"
                        value={filters.maxPrice || ''}
                        onChange={handleFilterChange}
                        className="border border-gray-300 rounded px-3 py-2 text-sm w-44 focus:outline-none focus:border-primary"
                    />
                    <input
                        type="number"
                        name="maxKilometrage"
                        placeholder="Km max"
                        value={filters.maxKilometrage || ''}
                        onChange={handleFilterChange}
                        className="border border-gray-300 rounded px-3 py-2 text-sm w-44 focus:outline-none focus:border-primary"
                    />

                    <button
                        type="submit"
                        className="px-5 py-2 bg-primary text-white text-sm rounded hover:opacity-90 transition"
                    >
                        Rechercher
                    </button>
                    <button
                        type="button"
                        onClick={handleReset}
                        className="px-4 py-2 text-sm border border-gray-300 rounded text-gray-500 hover:bg-gray-50 transition"
                    >
                        Réinitialiser
                    </button>
                </form>
            </div>

            {/* Résultats */}
            <div className="p-8">
                {isLoading && (
                    <p className="text-center text-gray-500 mt-8">Chargement des véhicules...</p>
                )}
                {error && (
                    <p className="text-center text-red-500 mt-8">{error}</p>
                )}
                {!isLoading && !error && vehicles.length === 0 && (
                    <p className="text-center text-gray-500 mt-8">
                        Aucun véhicule ne correspond à vos critères.
                    </p>
                )}

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {vehicles.map((vehicle) => (
                        <div key={vehicle.id} className="bg-white rounded-lg shadow-sm overflow-hidden relative">
                            <span className="absolute top-3 left-3 bg-primary text-white text-xs px-2 py-1 rounded z-10">
                                {vehicle.type === 'sale' ? 'Achat' : 'Location LLD'}
                            </span>

                            {vehicle.photoUrl ? (
                                <img
                                    src={vehicle.photoUrl}
                                    alt={`${vehicle.brand} ${vehicle.model}`}
                                    className="w-full h-44 object-cover"
                                />
                            ) : (
                                <div className="w-full h-44 bg-gray-100 flex items-center justify-center text-gray-400 text-sm">
                                    Pas de photo
                                </div>
                            )}

                            <div className="p-4">
                                <h3 className="font-semibold text-primary text-lg">
                                    {vehicle.brand} {vehicle.model}
                                </h3>
                                <p className="text-gray-500 text-sm mb-2">
                                    {vehicle.year} · {vehicle.kilometrage.toLocaleString()} km
                                </p>
                                {vehicle.description && (
                                    <p className="text-gray-600 text-sm mb-3 line-clamp-2">
                                        {vehicle.description}
                                    </p>
                                )}
                                <div className="flex justify-between items-center mt-3">
                                    <span className="text-secondary font-bold text-lg">
                                        {vehicle.price.toLocaleString()} €
                                        {vehicle.type === 'rental' && (
                                            <span className="text-gray-400 text-xs font-normal">/mois</span>
                                        )}
                                    </span>
                                    <button
                                        onClick={() => navigate(isAuthenticated ? `/vehicles/${vehicle.id}` : '/login')}
                                        className="px-4 py-2 bg-secondary text-white text-sm rounded hover:opacity-90 transition"
                                    >
                                        {isAuthenticated ? 'Déposer un dossier' : 'Se connecter'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
};

export default VehiclesPage;