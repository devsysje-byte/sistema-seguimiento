/**
 * Fachada pública del Módulo de Tesis de Grado.
 *
 * Contrato del módulo: lo único que otros módulos pueden importar de aquí
 * (`@/modules/tesis`). Expone rutas, store, utilidades del flujo y el
 * componente de cuenta regresiva. Reutiliza el dominio genérico de trámites
 * (store, línea de tiempo y etiquetas de estado) desde su fachada.
 */
import routes from './router/tesis.routes';

export { useTesisStore } from './stores/tesis';
export { FASES_TESIS, DESCRIPCION_ESTADO, faseDe, countdownDe, puedeSolicitarFechaDefensa, reoptarInfo } from './utils/flujo';

export default {
    nombre: 'tesis',
    rutas: routes,
};