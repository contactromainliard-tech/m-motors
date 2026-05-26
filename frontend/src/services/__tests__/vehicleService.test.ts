import api from '../api';
import {
    getVehicles,
    getVehicle,
    createVehicle,
    updateVehicle,
    toggleVehicleType,
    deleteVehicle,
} from '../vehicleService';

jest.mock('../api', () => ({
    __esModule: true,
    default: {
        get: jest.fn(),
        post: jest.fn(),
        put: jest.fn(),
        patch: jest.fn(),
        delete: jest.fn(),
    },
}));

const mockGet = api.get as jest.Mock;
const mockPost = api.post as jest.Mock;
const mockPut = api.put as jest.Mock;
const mockPatch = api.patch as jest.Mock;
const mockDelete = api.delete as jest.Mock;

describe('vehicleService', () => {
    afterEach(() => jest.clearAllMocks());

    describe('getVehicles', () => {
        it('appelle /vehicles sans paramètres quand aucun filtre', async () => {
            mockGet.mockResolvedValue({ data: [] });

            await getVehicles();

            expect(mockGet).toHaveBeenCalledWith('/vehicles?');
        });

        it('construit la query string avec tous les filtres', async () => {
            mockGet.mockResolvedValue({ data: [] });

            await getVehicles({ type: 'sale', brand: 'Renault', maxPrice: 15000, maxKilometrage: 50000 });

            const url = mockGet.mock.calls[0][0] as string;
            expect(url).toContain('type=sale');
            expect(url).toContain('brand=Renault');
            expect(url).toContain('maxPrice=15000');
            expect(url).toContain('maxKilometrage=50000');
        });

        it('omet les filtres non définis', async () => {
            mockGet.mockResolvedValue({ data: [] });

            await getVehicles({ type: 'rental' });

            const url = mockGet.mock.calls[0][0] as string;
            expect(url).toContain('type=rental');
            expect(url).not.toContain('brand');
            expect(url).not.toContain('maxPrice');
            expect(url).not.toContain('maxKilometrage');
        });

        it('retourne les données de la réponse', async () => {
            const vehicles = [{ id: 1, brand: 'Renault', model: 'Clio' }];
            mockGet.mockResolvedValue({ data: vehicles });

            const result = await getVehicles();

            expect(result).toEqual(vehicles);
        });
    });

    describe('getVehicle', () => {
        it('appelle /vehicles/:id avec le bon identifiant', async () => {
            const vehicle = { id: 42, brand: 'Peugeot', model: '308' };
            mockGet.mockResolvedValue({ data: vehicle });

            const result = await getVehicle(42);

            expect(mockGet).toHaveBeenCalledWith('/vehicles/42');
            expect(result).toEqual(vehicle);
        });
    });

    describe('createVehicle', () => {
        it('poste les données du véhicule et retourne l\'entité créée', async () => {
            const payload = { brand: 'Toyota', model: 'Yaris', year: 2022, price: 14000, type: 'sale' as const };
            const created = { id: 5, ...payload };
            mockPost.mockResolvedValue({ data: created });

            const result = await createVehicle(payload);

            expect(mockPost).toHaveBeenCalledWith('/vehicles', payload);
            expect(result).toEqual(created);
        });
    });

    describe('updateVehicle', () => {
        it('envoie un PUT avec les champs modifiés', async () => {
            const updated = { id: 3, price: 11000 };
            mockPut.mockResolvedValue({ data: updated });

            const result = await updateVehicle(3, { price: 11000 });

            expect(mockPut).toHaveBeenCalledWith('/vehicles/3', { price: 11000 });
            expect(result).toEqual(updated);
        });
    });

    describe('toggleVehicleType', () => {
        it('retourne vehicle depuis response.data.vehicle (pas response.data)', async () => {
            const vehicle = { id: 1, type: 'rental' };
            mockPatch.mockResolvedValue({ data: { vehicle } });

            const result = await toggleVehicleType(1);

            expect(mockPatch).toHaveBeenCalledWith('/vehicles/1/toggle-type');
            expect(result).toEqual(vehicle);
        });
    });

    describe('deleteVehicle', () => {
        it('appelle DELETE sur le bon endpoint', async () => {
            mockDelete.mockResolvedValue({ data: {} });

            await deleteVehicle(7);

            expect(mockDelete).toHaveBeenCalledWith('/vehicles/7');
        });
    });
});
