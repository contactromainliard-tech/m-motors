import React, { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { getProfile, updateProfile } from "../services/authService";

/**
 * Page de gestion du compte personnel du client.
 * Permet de consulter et modifier ses informations personnelles.
 */
const AccountPage: React.FC = () => {
    const { isAuthenticated, isAdmin, logout } = useAuth();
    const navigate = useNavigate();

    const [formData, setFormData] = useState({
        firstName: "",
        lastName: "",
        phone: "",
        address: "",
        city: "",
        zipCode: "",
    });
    const [email, setEmail] = useState("");
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [success, setSuccess] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!isAuthenticated) {
            navigate("/login");
            return;
        }
        loadProfile();
    }, []);

    /**
     * Charge les informations du profil depuis l API.
     */
    const loadProfile = async () => {
        try {
            const data = await getProfile();
            setEmail(data.email);
            setFormData({
                firstName: data.firstName || "",
                lastName: data.lastName || "",
                phone: data.phone || "",
                address: data.address || "",
                city: data.city || "",
                zipCode: data.zipCode || "",
            });
        } catch (err) {
            setError("Impossible de charger le profil.");
        } finally {
            setIsLoading(false);
        }
    };

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    /**
     * Sauvegarde les modifications du profil.
     */
    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSaving(true);
        setError(null);
        setSuccess(false);
        try {
            await updateProfile(formData);
            setSuccess(true);
        } catch (err: any) {
            setError("Erreur lors de la sauvegarde du profil.");
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <div className="min-h-screen bg-gray-100">
            {/* Navigation */}
            <nav className="bg-primary text-white px-8 py-4 flex justify-between items-center">
                <h1 className="text-2xl font-bold cursor-pointer" onClick={() => navigate("/")}>
                    M-Motors
                </h1>
                <div className="flex gap-3">
                    <button
                        onClick={() => navigate("/")}
                        className="px-4 py-2 text-sm border border-white rounded hover:bg-white hover:text-primary transition"
                    >
                        Catalogue
                    </button>
                    {!isAdmin && (
                        <button
                            onClick={() => navigate("/my-dossiers")}
                            className="px-4 py-2 text-sm border border-white rounded hover:bg-white hover:text-primary transition"
                        >
                            Mes dossiers
                        </button>
                    )}
                    <button
                        onClick={logout}
                        className="px-4 py-2 text-sm border border-white rounded hover:bg-white hover:text-primary transition"
                    >
                        Déconnexion
                    </button>
                </div>
            </nav>

            <div className="max-w-2xl mx-auto p-8">
                <h2 className="text-2xl font-bold text-primary mb-6">Mon compte</h2>

                {isLoading && <p className="text-center text-gray-500">Chargement...</p>}

                {!isLoading && (
                    <div className="bg-white rounded-lg shadow-sm p-6">
                        {success && (
                            <div className="bg-green-50 text-green-700 p-3 rounded mb-4 text-sm">
                                Profil mis à jour avec succès.
                            </div>
                        )}
                        {error && (
                            <div className="bg-red-50 text-red-600 p-3 rounded mb-4 text-sm">{error}</div>
                        )}

                        <form onSubmit={handleSubmit} className="space-y-4">
                            {/* Email non modifiable */}
                            <div>
                                <label className="block text-sm text-gray-700 mb-1">Email</label>
                                <input
                                    type="email"
                                    value={email}
                                    disabled
                                    className="w-full border border-gray-200 rounded px-3 py-2 text-sm bg-gray-50 text-gray-400 cursor-not-allowed"
                                />
                                <p className="text-xs text-gray-400 mt-1">L email ne peut pas etre modifié.</p>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm text-gray-700 mb-1">Prénom</label>
                                    <input
                                        type="text"
                                        name="firstName"
                                        value={formData.firstName}
                                        onChange={handleChange}
                                        className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm text-gray-700 mb-1">Nom</label>
                                    <input
                                        type="text"
                                        name="lastName"
                                        value={formData.lastName}
                                        onChange={handleChange}
                                        className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm text-gray-700 mb-1">Téléphone</label>
                                <input
                                    type="tel"
                                    name="phone"
                                    value={formData.phone}
                                    onChange={handleChange}
                                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                />
                            </div>

                            <div>
                                <label className="block text-sm text-gray-700 mb-1">Adresse</label>
                                <input
                                    type="text"
                                    name="address"
                                    value={formData.address}
                                    onChange={handleChange}
                                    placeholder="15 rue de la Paix"
                                    className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm text-gray-700 mb-1">Code postal</label>
                                    <input
                                        type="text"
                                        name="zipCode"
                                        value={formData.zipCode}
                                        onChange={handleChange}
                                        placeholder="75001"
                                        className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm text-gray-700 mb-1">Ville</label>
                                    <input
                                        type="text"
                                        name="city"
                                        value={formData.city}
                                        onChange={handleChange}
                                        placeholder="Paris"
                                        className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-primary"
                                    />
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={isSaving}
                                className="w-full py-2 bg-primary text-white rounded hover:opacity-90 transition disabled:opacity-50"
                            >
                                {isSaving ? "Sauvegarde..." : "Sauvegarder"}
                            </button>
                        </form>
                    </div>
                )}
            </div>
        </div>
    );
};

export default AccountPage;