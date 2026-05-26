import api from '../api';
import {
    createDossier,
    getMyDossiers,
    getAllDossiers,
    validateDossier,
} from '../dossierService';

jest.mock('../api', () => ({
    __esModule: true,
    default: {
        get: jest.fn(),
        post: jest.fn(),
        patch: jest.fn(),
    },
}));

const mockGet = api.get as jest.Mock;
const mockPost = api.post as jest.Mock;
const mockPatch = api.patch as jest.Mock;

describe('dossierService', () => {
    afterEach(() => jest.clearAllMocks());

    describe('createDossier', () => {
        it('poste vehicleId et type, retourne le dossier créé', async () => {
            const dossier = { id: 1, type: 'purchase', status: 'pending' };
            mockPost.mockResolvedValue({ data: dossier });

            const result = await createDossier(10, 'purchase');

            expect(mockPost).toHaveBeenCalledWith('/dossiers', { vehicleId: 10, type: 'purchase' });
            expect(result).toEqual(dossier);
        });

        it('fonctionne aussi pour un dossier de location', async () => {
            const dossier = { id: 2, type: 'rental', status: 'pending' };
            mockPost.mockResolvedValue({ data: dossier });

            const result = await createDossier(5, 'rental');

            expect(mockPost).toHaveBeenCalledWith('/dossiers', { vehicleId: 5, type: 'rental' });
            expect(result.type).toBe('rental');
        });
    });

    describe('getMyDossiers', () => {
        it('appelle /dossiers/my-dossiers et retourne la liste', async () => {
            const dossiers = [{ id: 1, status: 'pending' }, { id: 2, status: 'approved' }];
            mockGet.mockResolvedValue({ data: dossiers });

            const result = await getMyDossiers();

            expect(mockGet).toHaveBeenCalledWith('/dossiers/my-dossiers');
            expect(result).toHaveLength(2);
        });
    });

    describe('getAllDossiers', () => {
        it('appelle /dossiers (endpoint admin)', async () => {
            mockGet.mockResolvedValue({ data: [] });

            await getAllDossiers();

            expect(mockGet).toHaveBeenCalledWith('/dossiers');
        });
    });

    describe('validateDossier', () => {
        it('envoie le statut sans commentaire quand non fourni', async () => {
            const dossier = { id: 1, status: 'approved' };
            mockPatch.mockResolvedValue({ data: dossier });

            const result = await validateDossier(1, 'approved');

            expect(mockPatch).toHaveBeenCalledWith('/dossiers/1/validate', {
                status: 'approved',
                comment: undefined,
            });
            expect(result).toEqual(dossier);
        });

        it('envoie le statut avec le commentaire quand fourni', async () => {
            const dossier = { id: 2, status: 'rejected' };
            mockPatch.mockResolvedValue({ data: dossier });

            await validateDossier(2, 'rejected', 'Documents insuffisants.');

            expect(mockPatch).toHaveBeenCalledWith('/dossiers/2/validate', {
                status: 'rejected',
                comment: 'Documents insuffisants.',
            });
        });

        it('supporte le statut in_progress', async () => {
            mockPatch.mockResolvedValue({ data: { id: 3, status: 'in_progress' } });

            const result = await validateDossier(3, 'in_progress');

            expect(result.status).toBe('in_progress');
        });
    });
});
