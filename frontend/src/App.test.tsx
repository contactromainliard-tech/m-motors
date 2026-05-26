import { render, screen, act } from '@testing-library/react';
import App from './App';

// jest.mock est hoissté avant les imports — on utilise require() à l'intérieur
jest.mock('./pages/VehiclesPage', () => {
    const React = require('react');
    return () => React.createElement('main', { 'data-testid': 'vehicles-page' }, 'VehiclesPage');
});
jest.mock('./pages/LoginPage', () => {
    const React = require('react');
    return () => React.createElement('main', { 'data-testid': 'login-page' }, 'LoginPage');
});
jest.mock('./pages/RegisterPage', () => () => null);
jest.mock('./pages/VehicleDetailPage', () => () => null);
jest.mock('./pages/MyDossiersPage', () => () => null);
jest.mock('./pages/AdminPage', () => () => null);
jest.mock('./pages/AccountPage', () => () => null);

describe('App', () => {
    it('se monte sans lever d\'exception', async () => {
        await act(async () => {
            render(<App />);
        });
    });

    it('rend un nœud DOM au premier montage', async () => {
        let container: HTMLElement;
        await act(async () => {
            ({ container } = render(<App />));
        });
        expect(container!.firstChild).not.toBeNull();
    });

    it('affiche la page Véhicules sur la route /', async () => {
        await act(async () => {
            render(<App />);
        });
        expect(screen.getByTestId('vehicles-page')).toBeInTheDocument();
    });
});
