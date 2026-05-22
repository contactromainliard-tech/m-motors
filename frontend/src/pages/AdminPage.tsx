import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Vehicle, Dossier } from '../types';
import { getVehicles, createVehicle, toggleVehicleType, uploadVehiclePhoto, updateVehicle, deleteVehicle } from '../services/vehicleService';
import { getAllDossiers, validateDossier } from '../services/dossierService';
import { useAuth } from '../context/AuthContext';

/**
 * Page back-office de gestion des véhicules et des dossiers.
 * Accessible uniquement aux administrateurs.
 */
const AdminPage: React.FC = () => {
    const { isAuthenticated, isAdmin, logout } = useAuth();
    const navigate = useNavigate();

    const [activeTab, setActiveTab] = useState<'vehicles' | 'dossiers'>('vehicles');
    const [vehicles, setVehicles] = useState<Vehicle[]>([]);
    const [dossiers, setDossiers] = useState<Dossier[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    // Formulaire ajout véhicule
    const [showForm, setShowForm] = useState(false);
    const [vehicleForm, setVehicleForm] = useState({
        brand: '', model: '', year: '', kilometrage: '', price: '', type: 'sale', description: '',
    });
    const [photoFile, setPhotoFile] = useState<File | null>(null);
    const [formError, setFormError] = useState<string | null>(null);
    const [formSuccess, setFormSuccess] = useState(false);

    // Formulaire modification véhicule
    const [editingVehicle, setEditingVehicle] = useState<Vehicle | null>(null);
    const [editForm, setEditForm] = useState({
        brand: '', model: '', year: '', kilometrage: '', price: '', type: 'sale', description: '',
    });

    useEffect(() => {
        if (!isAuthenticated || !isAdmin) {
            navigate('/');
            return;
        }
        loadData();
    }, []);

    /**
     * Charge les véhicules et les dossiers depuis l'API.
     */
    const loadData = async () => {
        setIsLoading(true);
        try {
            const [vehiclesData, dossiersData] = await Promise.all([
                getVehicles(),
                getAllDossiers(),
            ]);
            setVehicles(vehiclesData);
            setDossiers(dossiersData);
        } catch (err) {
            console.error('Erreur lors du chargement des données', err);
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * Soumet le formulaire d'ajout d'un véhicule avec photo optionnelle.
     */
    const handleVehicleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setFormError(null);
        try {
            const newVehicle = await createVehicle({
                brand: vehicleForm.brand,
                model: vehicleForm.model,
                year: Number(vehicleForm.year),
                kilometrage: Number(vehicleForm.kilometrage),
                price: Number(vehicleForm.price),
                type: vehicleForm.type as 'sale' | 'rental',
                description: vehicleForm.description || undefined,
            });

            // Upload de la photo si un fichier est sélectionné
            if (photoFile && newVehicle.id) {
                const formData = new FormData();
                formData.append('photo', photoFile);
                await uploadVehiclePhoto(newVehicle.id, formData);
            }

            setFormSuccess(true);
            setPhotoFile(null);
            setVehicleForm({ brand: '', model: '', year: '', kilometrage: '', price: '', type: 'sale', description: '' });
            setShowForm(false);
            loadData();
        } catch (err: any) {
            setFormError(err.response?.data?.message || 'Erreur lors de la création du véhicule.');
        }
    };

    /**
     * Prépare le formulaire de modification d'un véhicule.
     */
    const handleEditVehicle = (vehicle: Vehicle) => {
        setEditingVehicle(vehicle);
        setEditForm({
            brand: vehicle.brand,
            model: vehicle.model,
            year: vehicle.year.toString(),
            kilometrage: vehicle.kilometrage.toString(),
            price: vehicle.price.toString(),
            type: vehicle.type,
            description: vehicle.description || '',
        });
    };

    /**
     * Soumet le formulaire de modification d'un véhicule.
     */
    const handleEditSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingVehicle) return;
        setFormError(null);
        try {
            await updateVehicle(editingVehicle.id, {
                brand: editForm.brand,
                model: editForm.model,
                year: Number(editForm.year),
                kilometrage: Number(editForm.kilometrage),
                price: Number(editForm.price),
                type: editForm.type as 'sale' | 'rental',
                description: editForm.description || undefined,
            });
            setEditingVehicle(null);
            loadData();
        } catch (err: any) {
            setFormError(err.response?.data?.message || 'Erreur lors de la modification.');
        }
    };

    /**
     * Supprime un véhicule après confirmation.
     */
    const handleDeleteVehicle = async (vehicle: Vehicle) => {
        if (!window.confirm(`Supprimer ${vehicle.brand} ${vehicle.model} ?`)) return;
        try {
            await deleteVehicle(vehicle.id);
            loadData();
        } catch (err: any) {
            alert(err.response?.data?.message || 'Erreur lors de la suppression.');
        }
    };

    /**
     * Bascule le type d'un véhicule entre location et vente.
     */
    const handleToggleType = async (id: number) => {
        try {
            await toggleVehicleType(id);
            loadData();
        } catch (err) {
            console.error('Erreur lors de la bascule du type', err);
        }
    };

    /**
     * Valide, met en cours de traitement ou refuse un dossier client.
     */
    const handleValidateDossier = async (id: number, status: 'approved' | 'rejected' | 'in_progress') => {
        const comment = status === 'rejected'
            ? window.prompt('Motif de refus (optionnel) :') || undefined
            : undefined;
        try {
            await validateDossier(id, status, comment);
            loadData();
        } catch (err) {
            console.error('Erreur lors de la validation du dossier', err);
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
            case 'approved': return 'Validé';
            case 'rejected': return 'Refusé';
            case 'in_progress': return 'En cours de traitement';
            default: return 'En attente';
        }
    };

    return (
        <div className="min-h-screen bg-gray-100">
            {/* Modal de modification de véhicule */}
            {editingVehicle && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg shadow-lg p-6 w-full max-w-xl">
                        <h3 className="font-semibold text-primary mb-4">
                            Modifier {editingVehicle.brand} {editingVehicle.model}
                        </h3>
                        {formError && (
                            <div className="bg-red-50 text-red-600 p-3 rounded mb-4 text-sm">{formError}</div>
                        )}
                        <form onSubmit={handleEditSubmit} className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm text-gray-700 mb-1">Marque</label>
                                <input
                                    type="text"
                                    value={editForm.brand}
                                    onChange={(e) => setEditForm({ ...editForm, brand: e.target.value })}
                                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm text-gray-700 mb-1">Modèle</label>
                                <input
                                    type="text"
                                    value={editForm.model}
                                    onChange={(e) => setEditForm({ ...editForm, model: e.target.value })}
                                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm text-gray-700 mb-1">Année</label>
                                <input
                                    type="number"
                                    value={editForm.year}
                                    onChange={(e) => setEditForm({ ...editForm, year: e.target.value })}
                                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm text-gray-700 mb-1">Kilométrage</label>
                                <input
                                    type="number"
                                    value={editForm.kilometrage}
                                    onChange={(e) => setEditForm({ ...editForm, kilometrage: e.target.value })}
                                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm text-gray-700 mb-1">Prix (€)</label>
                                <input
                                    type="number"
                                    value={editForm.price}
                                    onChange={(e) => setEditForm({ ...editForm, price: e.target.value })}
                                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm text-gray-700 mb-1">Type</label>
                                <select
                                    value={editForm.type}
                                    onChange={(e) => setEditForm({ ...editForm, type: e.target.value })}
                                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                >
                                    <option value="sale">Achat</option>
                                    <option value="rental">Location LLD</option>
                                </select>
                            </div>
                            <div className="col-span-2">
                                <label className="block text-sm text-gray-700 mb-1">Description</label>
                                <input
                                    type="text"
                                    value={editForm.description}
                                    onChange={(e) => setEditForm({ ...editForm, description: e.target.value })}
                                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                />
                            </div>
                            <div className="col-span-2 flex gap-3">
                                <button
                                    type="submit"
                                    className="px-6 py-2 bg-primary text-white text-sm rounded hover:opacity-90 transition"
                                >
                                    Sauvegarder
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setEditingVehicle(null)}
                                    className="px-6 py-2 border border-gray-300 text-gray-600 text-sm rounded hover:bg-gray-50 transition"
                                >
                                    Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Navigation */}
            <nav className="bg-primary text-white px-8 py-4 flex justify-between items-center">
                <h1 className="text-2xl font-bold cursor-pointer" onClick={() => navigate('/')}>
                    M-Motors — Back-office
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

            <div className="max-w-6xl mx-auto p-8">
                {/* Onglets */}
                <div className="flex gap-4 mb-6 border-b border-gray-200">
                    <button
                        onClick={() => setActiveTab('vehicles')}
                        className={`pb-3 px-1 text-sm font-medium border-b-2 transition ${activeTab === 'vehicles'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-gray-500 hover:text-primary'
                            }`}
                    >
                        Véhicules ({vehicles.length})
                    </button>
                    <button
                        onClick={() => setActiveTab('dossiers')}
                        className={`pb-3 px-1 text-sm font-medium border-b-2 transition ${activeTab === 'dossiers'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-gray-500 hover:text-primary'
                            }`}
                    >
                        Dossiers ({dossiers.length})
                    </button>
                </div>

                {isLoading && <p className="text-center text-gray-500">Chargement...</p>}

                {/* Onglet véhicules */}
                {!isLoading && activeTab === 'vehicles' && (
                    <div>
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-semibold text-primary">Gestion des véhicules</h2>
                            <button
                                onClick={() => setShowForm(!showForm)}
                                className="px-4 py-2 bg-secondary text-white text-sm rounded hover:opacity-90 transition"
                            >
                                {showForm ? 'Annuler' : '+ Ajouter un véhicule'}
                            </button>
                        </div>

                        {/* Formulaire d'ajout de véhicule */}
                        {showForm && (
                            <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
                                <h3 className="font-semibold text-primary mb-4">Nouveau véhicule</h3>
                                {formError && (
                                    <div className="bg-red-50 text-red-600 p-3 rounded mb-4 text-sm">{formError}</div>
                                )}
                                <form onSubmit={handleVehicleSubmit} className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm text-gray-700 mb-1">Marque</label>
                                        <input
                                            type="text"
                                            value={vehicleForm.brand}
                                            onChange={(e) => setVehicleForm({ ...vehicleForm, brand: e.target.value })}
                                            className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm text-gray-700 mb-1">Modèle</label>
                                        <input
                                            type="text"
                                            value={vehicleForm.model}
                                            onChange={(e) => setVehicleForm({ ...vehicleForm, model: e.target.value })}
                                            className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm text-gray-700 mb-1">Année</label>
                                        <input
                                            type="number"
                                            value={vehicleForm.year}
                                            onChange={(e) => setVehicleForm({ ...vehicleForm, year: e.target.value })}
                                            className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm text-gray-700 mb-1">Kilométrage</label>
                                        <input
                                            type="number"
                                            value={vehicleForm.kilometrage}
                                            onChange={(e) => setVehicleForm({ ...vehicleForm, kilometrage: e.target.value })}
                                            className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm text-gray-700 mb-1">Prix (€)</label>
                                        <input
                                            type="number"
                                            value={vehicleForm.price}
                                            onChange={(e) => setVehicleForm({ ...vehicleForm, price: e.target.value })}
                                            className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm text-gray-700 mb-1">Type</label>
                                        <select
                                            value={vehicleForm.type}
                                            onChange={(e) => setVehicleForm({ ...vehicleForm, type: e.target.value })}
                                            className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                        >
                                            <option value="sale">Achat</option>
                                            <option value="rental">Location LLD</option>
                                        </select>
                                    </div>
                                    <div className="col-span-2">
                                        <label className="block text-sm text-gray-700 mb-1">Description (optionnel)</label>
                                        <input
                                            type="text"
                                            value={vehicleForm.description}
                                            onChange={(e) => setVehicleForm({ ...vehicleForm, description: e.target.value })}
                                            className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                        />
                                    </div>
                                    <div className="col-span-2">
                                        <label className="block text-sm text-gray-700 mb-1">Photo (optionnel)</label>
                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png"
                                            onChange={(e) => setPhotoFile(e.target.files?.[0] || null)}
                                            className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                        />
                                        <p className="text-xs text-gray-400 mt-1">JPG ou PNG, 5 Mo maximum.</p>
                                    </div>
                                    <div className="col-span-2">
                                        <button
                                            type="submit"
                                            className="px-6 py-2 bg-primary text-white text-sm rounded hover:opacity-90 transition"
                                        >
                                            Ajouter le véhicule
                                        </button>
                                    </div>
                                </form>
                            </div>
                        )}

                        {/* Liste des véhicules */}
                        <div className="bg-white rounded-lg shadow-sm overflow-hidden">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th className="text-left px-4 py-3 text-gray-600 font-medium">Véhicule</th>
                                        <th className="text-left px-4 py-3 text-gray-600 font-medium">Année</th>
                                        <th className="text-left px-4 py-3 text-gray-600 font-medium">Kilométrage</th>
                                        <th className="text-left px-4 py-3 text-gray-600 font-medium">Prix</th>
                                        <th className="text-left px-4 py-3 text-gray-600 font-medium">Type</th>
                                        <th className="text-left px-4 py-3 text-gray-600 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {vehicles.map((vehicle) => (
                                        <tr key={vehicle.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-3 font-medium text-primary">
                                                {vehicle.brand} {vehicle.model}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600">{vehicle.year}</td>
                                            <td className="px-4 py-3 text-gray-600">
                                                {vehicle.kilometrage.toLocaleString()} km
                                            </td>
                                            <td className="px-4 py-3 text-gray-600">
                                                {vehicle.price.toLocaleString()} €
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className={`px-2 py-1 rounded text-xs font-medium ${vehicle.type === 'sale'
                                                    ? 'bg-blue-100 text-blue-700'
                                                    : 'bg-purple-100 text-purple-700'
                                                    }`}>
                                                    {vehicle.type === 'sale' ? 'Achat' : 'Location'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex gap-2">
                                                    <button
                                                        onClick={() => handleEditVehicle(vehicle)}
                                                        className="px-3 py-1 text-xs bg-primary text-white rounded hover:opacity-90 transition"
                                                    >
                                                        Modifier
                                                    </button>
                                                    <button
                                                        onClick={() => handleToggleType(vehicle.id)}
                                                        className="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-100 transition"
                                                    >
                                                        Basculer
                                                    </button>
                                                    <button
                                                        onClick={() => handleDeleteVehicle(vehicle)}
                                                        className="px-3 py-1 text-xs bg-red-500 text-white rounded hover:opacity-90 transition"
                                                    >
                                                        Supprimer
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* Onglet dossiers */}
                {!isLoading && activeTab === 'dossiers' && (
                    <div>
                        <h2 className="text-xl font-semibold text-primary mb-4">Gestion des dossiers</h2>
                        <div className="bg-white rounded-lg shadow-sm overflow-hidden">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th className="text-left px-4 py-3 text-gray-600 font-medium">Client</th>
                                        <th className="text-left px-4 py-3 text-gray-600 font-medium">Véhicule</th>
                                        <th className="text-left px-4 py-3 text-gray-600 font-medium">Type</th>
                                        <th className="text-left px-4 py-3 text-gray-600 font-medium">Date</th>
                                        <th className="text-left px-4 py-3 text-gray-600 font-medium">Statut</th>
                                        <th className="text-left px-4 py-3 text-gray-600 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {dossiers.map((dossier) => (
                                        <tr key={dossier.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-3">
                                                <p className="font-medium text-primary">
                                                    {dossier.client.firstName} {dossier.client.lastName}
                                                </p>
                                                <p className="text-gray-400 text-xs">{dossier.client.email}</p>
                                            </td>
                                            <td className="px-4 py-3 text-gray-600">
                                                {dossier.vehicle.brand} {dossier.vehicle.model}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600">
                                                {dossier.type === 'purchase' ? 'Achat' : 'Location'}
                                            </td>
                                            <td className="px-4 py-3 text-gray-500 text-xs">
                                                {new Date(dossier.createdAt).toLocaleDateString('fr-FR')}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className={`px-2 py-1 rounded text-xs font-medium ${getStatusStyle(dossier.status)}`}>
                                                    {getStatusLabel(dossier.status)}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3">
                                                {/* Boutons d'action pour dossier en attente */}
                                                {dossier.status === 'pending' && (
                                                    <div className="flex gap-2">
                                                        <button
                                                            onClick={() => handleValidateDossier(dossier.id, 'in_progress')}
                                                            className="px-3 py-1 text-xs bg-blue-500 text-white rounded hover:opacity-90 transition"
                                                        >
                                                            En cours
                                                        </button>
                                                        <button
                                                            onClick={() => handleValidateDossier(dossier.id, 'approved')}
                                                            className="px-3 py-1 text-xs bg-green-500 text-white rounded hover:opacity-90 transition"
                                                        >
                                                            Valider
                                                        </button>
                                                        <button
                                                            onClick={() => handleValidateDossier(dossier.id, 'rejected')}
                                                            className="px-3 py-1 text-xs bg-red-500 text-white rounded hover:opacity-90 transition"
                                                        >
                                                            Refuser
                                                        </button>
                                                    </div>
                                                )}
                                                {/* Boutons d'action pour dossier en cours de traitement */}
                                                {dossier.status === 'in_progress' && (
                                                    <div className="flex gap-2">
                                                        <button
                                                            onClick={() => handleValidateDossier(dossier.id, 'approved')}
                                                            className="px-3 py-1 text-xs bg-green-500 text-white rounded hover:opacity-90 transition"
                                                        >
                                                            Valider
                                                        </button>
                                                        <button
                                                            onClick={() => handleValidateDossier(dossier.id, 'rejected')}
                                                            className="px-3 py-1 text-xs bg-red-500 text-white rounded hover:opacity-90 transition"
                                                        >
                                                            Refuser
                                                        </button>
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default AdminPage;