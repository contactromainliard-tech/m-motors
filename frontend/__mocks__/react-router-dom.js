// Shim CJS pour Jest 27 (react-scripts 5) qui ne supporte pas le champ "exports"
// de react-router-dom v7. Utilisé uniquement en environnement de test.
const React = require('react');

const BrowserRouter = ({ children }) => React.createElement('div', null, children);
const Routes = ({ children }) => React.createElement('div', null, children);
const Route = ({ element }) => element || null;
const Navigate = () => null;
const Link = ({ children, to }) => React.createElement('a', { href: to }, children);
const NavLink = ({ children, to }) => React.createElement('a', { href: to }, children);

const useNavigate = () => jest.fn();
const useParams = () => ({});
const useLocation = () => ({ pathname: '/', search: '', hash: '', state: null });

module.exports = {
    BrowserRouter,
    Routes,
    Route,
    Navigate,
    Link,
    NavLink,
    useNavigate,
    useParams,
    useLocation,
};
