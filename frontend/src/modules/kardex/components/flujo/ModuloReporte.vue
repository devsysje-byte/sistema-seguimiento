<template>
  <ModuloTarjeta
    numero="5"
    titulo="Reporte"
    descripcion="Genere el reporte consolidado del trámite de titulación."
    icono="bar-chart"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado && datos" class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-4 py-3">
      <p class="flex items-center gap-2 text-sm font-semibold text-emerald-200">
        <AppIcon name="check-circle" :size="16" class="text-emerald-400" />
        Reporte generado el {{ fechaLegible(datos.generado) }}
      </p>
      <p v-if="datos.formato" class="text-xs text-emerald-400 mt-0.5">Formato: {{ datos.formato.toUpperCase() }}</p>

      <div class="mt-3 flex flex-wrap gap-2">
        <button class="btn-ghost !py-2 px-4" :disabled="cargando || generando" @click="visualizar">
          <AppIcon name="eye" :size="15" />
          Ver reporte
        </button>
        <button class="btn-warm px-4 !py-2" :disabled="cargando || generando" @click="descargarActual">
          <AppIcon name="download" :size="15" />
          Descargar de nuevo
        </button>
      </div>
    </div>

    <div v-else-if="bloqueado">
      <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="shield" :size="15" />
        Complete el módulo anterior para generar el reporte.
      </div>
    </div>

    <div v-else>
      <div class="rounded-xl border border-white/10 bg-slate-900/40 overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-white/10">
          <h5 class="text-xs font-bold uppercase tracking-wider text-orange-300 inline-flex items-center gap-2">
            <AppIcon name="eye" :size="14" />
            Vista previa del reporte
          </h5>
          <span class="text-[11px] text-slate-400">{{ filas.length }} registros</span>
        </div>
        <div class="max-h-56 overflow-auto">
          <table class="w-full text-sm">
            <tbody>
              <tr v-for="fila in filas" :key="fila[0]" class="border-b border-white/5 last:border-0">
                <td class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">
                  {{ fila[0] }}
                </td>
                <td class="px-4 py-2 text-slate-200">{{ fila[1] }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
        <p class="text-xs text-slate-400 inline-flex items-center gap-1.5">
          <AppIcon name="zap" :size="14" />
          Este módulo se genera automáticamente. El PDF se puede descargar cuando se necesite.
        </p>
        <div class="flex flex-wrap items-center gap-2">
          <button class="btn-ghost !py-2.5 px-5" :disabled="cargando || generando" @click="visualizar">
            <AppIcon name="eye" :size="16" />
            Vista previa
          </button>
          <button class="btn-warm px-6" :disabled="cargando || generando" @click="exportarReporte">
            <AppIcon v-if="cargando || generando" name="loader" :size="18" class="animate-spin" />
            <AppIcon v-else name="download" :size="18" />
            <span class="uppercase tracking-wide">Generar Reporte PDF</span>
          </button>
        </div>
      </div>
    </div>
  </ModuloTarjeta>

  <!-- Área imprimible (fuera de pantalla): el PDF se genera 100% en el frontend -->
  <div class="reporte-oculto" aria-hidden="true">
    <div ref="contenedorReporte" class="reporte-pagina">
      <!-- 1. Encabezado del reporte -->
      <header class="reporte-encabezado">
        <div class="reporte-encabezado__logo">LOGOTIPO</div>
        <div class="reporte-encabezado__titulares">
          <h1>REPORTE EJECUTIVO DE TESIS</h1>
          <p>Fecha de emisión: <strong>{{ fechaEmision }}</strong></p>
          <p>Emitido por: <strong>{{ usuarioEmisor }}</strong></p>
        </div>
      </header>

      <!-- 2. Datos del estudiante -->
      <section class="reporte-bloque reporte-bloque--estudiante">
        <h2>Datos del Estudiante</h2>
        <div class="reporte-grid reporte-grid--2">
          <div class="reporte-campo reporte-campo--ancho2">
            <span>Nombre completo</span>
            <strong>{{ datosEstudiante.nombre }}</strong>
          </div>
          <div class="reporte-campo">
            <span>Carrera / Programa</span>
            <strong>{{ datosEstudiante.carrera }}</strong>
          </div>
          <div class="reporte-campo">
            <span>Matrícula (R.U.)</span>
            <strong>{{ datosEstudiante.matricula }}</strong>
          </div>
          <div class="reporte-campo">
            <span>C.I.</span>
            <strong>{{ datosEstudiante.ci }}</strong>
          </div>
          <div class="reporte-campo reporte-campo--ancho2">
            <span>Correo institucional</span>
            <strong>{{ datosEstudiante.correo }}</strong>
          </div>
        </div>
      </section>

      <!-- 3. KPIs -->
      <section class="reporte-kpis">
        <div class="reporte-kpi reporte-kpi--enfasis">
          <span class="reporte-kpi__label">Avance del Proceso</span>
          <strong class="reporte-kpi__valor">{{ avance }}%</strong>
          <div class="reporte-progreso">
            <span :style="{ width: avance + '%' }"></span>
          </div>
        </div>
        <div class="reporte-kpi">
          <span class="reporte-kpi__label">Estado General</span>
          <span class="reporte-kpi__badge">
            <span :class="badgeClass(estadoGeneral)">{{ estadoGeneral }}</span>
          </span>
        </div>
        <div class="reporte-kpi">
          <span class="reporte-kpi__label">Días Transcurridos</span>
          <strong class="reporte-kpi__valor">{{ diasTranscurridos }}</strong>
          <span class="reporte-kpi__meta">desde el inicio del trámite</span>
        </div>
      </section>

      <!-- 4. Módulo 1 -->
      <section class="reporte-bloque">
        <h2><span class="reporte-numero">01</span>Módulo 1 — Perfil de Tesis</h2>
        <table class="reporte-tabla">
          <thead>
            <tr>
              <th>Documento</th>
              <th>Estado</th>
              <th>Archivo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in perfilTesis" :key="d.etiqueta">
              <td>{{ d.etiqueta }}</td>
              <td><span :class="badgeClass(d.estado)">{{ d.estado }}</span></td>
              <td class="reporte-tabla__archivo">{{ d.archivo }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- 5. Módulo 2 -->
      <section class="reporte-bloque">
        <h2><span class="reporte-numero">02</span>Módulo 2 — Aprobación del Tema</h2>
        <div class="reporte-grid reporte-grid--2">
          <div class="reporte-campo">
            <span>N.º Resolución HCC</span>
            <strong>{{ aprobacionTema.numero_resolucion || '—' }}</strong>
          </div>
          <div class="reporte-campo">
            <span>Fecha de Resolución</span>
            <strong>{{ fechaLegible(aprobacionTema.fecha_resolucion) || '—' }}</strong>
          </div>
          <div class="reporte-campo reporte-campo--ancho2">
            <span>Tema de Investigación</span>
            <strong>{{ aprobacionTema.tema_investigacion || '—' }}</strong>
          </div>
          <div class="reporte-campo reporte-campo--ancho2 reporte-campo--acento">
            <span>Docente Tutor</span>
            <strong>{{ nombreTutor }}</strong>
          </div>
        </div>
      </section>

      <!-- 6. Módulo 3 -->
      <section class="reporte-bloque">
        <h2><span class="reporte-numero">03</span>Módulo 3 — Tribunal Revisor</h2>
        <table class="reporte-tabla">
          <thead>
            <tr>
              <th>Miembro</th>
              <th>Rol / Cargo</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in tribunalRevisor" :key="m.rol">
              <td>{{ m.nombre }}</td>
              <td>{{ m.rol }}</td>
              <td><span :class="badgeClass(m.estado)">{{ m.estado }}</span></td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- 7. Módulo 4 -->
      <section class="reporte-bloque">
        <h2><span class="reporte-numero">04</span>Módulo 4 — Defensa</h2>
        <div class="reporte-grid reporte-grid--2">
          <div class="reporte-campo">
            <span>Fecha de Defensa</span>
            <strong>{{ fechaLegible(defensa.fecha_defensa) || '—' }}</strong>
          </div>
          <div class="reporte-campo">
            <span>Hora</span>
            <strong>{{ defensa.hora || '—' }}</strong>
          </div>
          <div class="reporte-campo">
            <span>Lugar</span>
            <strong>{{ defensa.lugar || '—' }}</strong>
          </div>
          <div class="reporte-campo">
            <span>N.º Resolución Final</span>
            <strong>{{ defensa.numero_resolucion || '—' }}</strong>
          </div>
          <div class="reporte-campo reporte-campo--ancho2 reporte-campo--acento reporte-campo--nota">
            <span>Resultado / Calificación</span>
            <strong>{{ resultadoDefensa }}</strong>
          </div>
        </div>
      </section>

      <!-- 8. Pie de página -->
      <footer class="reporte-pie">
        <p>
          Documento generado automáticamente por el Sistema de Seguimiento de Titulación. La información
          contenida es de carácter confidencial y de uso exclusivo del proceso de titulación. Toda
          reproducción total o parcial requiere autorización expresa de las autoridades académicas.
        </p>
        <p class="reporte-pie__marca">SISTEMA DE SEGUIMIENTO DE TESIS · {{ fechaEmision }}</p>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import html2pdf from 'html2pdf.js';
import { formatoEstado } from '@/modules/tramites';
import {
  DOCUMENTOS_PERFIL,
  datosDeModulo,
  pctTitulacion,
} from '@/modules/tesis/utils/flujoTitulacion';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import { useAuthStore } from '@/modules/auth/stores/auth';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from './ModuloTarjeta.vue';

const props = defineProps({
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  datos: { type: Object, default: null },
  tramite: { type: Object, default: null },
  docentes: { type: Array, default: () => [] },
  cargando: { type: Boolean, default: false },
  simulado: { type: Boolean, default: false },
});

const emit = defineEmits(['guardar']);

const authStore = useAuthStore();
const toastStore = useToastStore();

const contenedorReporte = ref(null);
const generando = ref(false);

// --- Fuente de datos: `tramite.hitos.modulos` (datos guardados de c/u de los 4 módulos) ---
const postulante = computed(() => props.tramite?.estudiante || {});

const datosEstudiante = computed(() => {
  const e = postulante.value;
  const nombre = `${e.nombres || ''} ${e.apellidos || ''}`.trim();
  return {
    nombre: nombre || '—',
    carrera: e.carrera || e.programa || props.tramite?.modalidad?.nombre || '—',
    matricula: e.registro_universitario || '—',
    ci: e.ci || '—',
    correo: e.correo || e.email || '—',
  };
});

const perfilDatos = computed(() => datosDeModulo(props.tramite, 'perfil_tesis') || {});
const perfilTesis = computed(() =>
  DOCUMENTOS_PERFIL.map((d) => ({
    etiqueta: d.etiqueta,
    estado: perfilDatos.value.documentos?.[d.tipo]?.marcado ? 'COMPLETADO' : 'PENDIENTE',
    archivo: perfilDatos.value.documentos?.[d.tipo]?.archivo?.nombre || '—',
  })),
);

const aprobacionTema = computed(() => datosDeModulo(props.tramite, 'aprobacion_tema') || {});

const nombreTutor = computed(() => {
  const t = aprobacionTema.value;
  const porDocentes = props.docentes?.find((d) => d.id_usuario === Number(t.tutor_id));
  if (porDocentes) return `${porDocentes.nombres} ${porDocentes.apellidos}`;
  const tr = props.tramite?.tutor;
  if (tr) return `${tr.nombres} ${tr.apellidos}`;
  return t.tutor_id ? `Docente #${t.tutor_id}` : '—';
});

const tribunalDatos = computed(() => datosDeModulo(props.tramite, 'tribunal_revisor') || {});
const tribunalRevisor = computed(() =>
  (tribunalDatos.value.tribunal || []).map((m) => ({
    rol: m.rol.charAt(0).toUpperCase() + m.rol.slice(1),
    nombre: m.nombre || '—',
    estado: 'ASIGNADO',
  })),
);

const defensa = computed(() => datosDeModulo(props.tramite, 'defensa') || {});

const resultadoDefensa = computed(() => {
  const nota = defensa.value.nota_final ?? defensa.value.resultado;
  if (nota === null || nota === undefined || nota === '') return '—';
  const n = Number(nota);
  return Number.isNaN(n) ? String(nota) : `${n} / 100`;
});

// --- KPIs ---
const avance = computed(() => pctTitulacion(props.tramite));

const estadoGeneral = computed(() => {
  if (avance.value >= 100) return 'COMPLETADO';
  if (avance.value > 0) return 'EN PROCESO';
  return 'PENDIENTE';
});

const diasTranscurridos = computed(() => {
  const inicio = props.tramite?.hitos?.solicitud_presentada || props.tramite?.created_at;
  if (!inicio) return 0;
  const d = new Date(String(inicio).slice(0, 10));
  if (Number.isNaN(d.getTime())) return 0;
  return Math.max(0, Math.floor((Date.now() - d.getTime()) / 86_400_000));
});

const fechaEmision = computed(() =>
  new Date().toLocaleDateString('es-PE', { day: '2-digit', month: 'long', year: 'numeric' }),
);

const usuarioEmisor = computed(() => {
  const u = authStore.user;
  if (!u) return '—';
  return `${u.nombres || ''} ${u.apellidos || ''}`.trim() || u.username || '—';
});

// --- Vista previa (tabla legible dentro del módulo) ---
const historial = computed(() =>
  [...(props.tramite?.estados || [])].sort((a, b) => new Date(a.created_at) - new Date(b.created_at)),
);

const filas = computed(() => {
  const t = props.tramite;
  if (!t) return [];
  const f = [
    ['Postulante', `${postulante.value.nombres || ''} ${postulante.value.apellidos || ''}`.trim()],
    ['CI', postulante.value.ci || '—'],
    ['Registro Universitario', postulante.value.registro_universitario || '—'],
    ['Modalidad', t.modalidad?.nombre || '—'],
    ['Trámite N.º', t.id_tramite],
    ['Estado actual', formatoEstado(t.estado_actual)],
    ['Tutor asignado', t.tutor ? `${t.tutor.nombres} ${t.tutor.apellidos}` : nombreTutor.value],
    ...historial.value.map((e) => [
      `Hito: ${formatoEstado(e.nombre_estado)}`,
      `${fechaLegible(e.created_at)} ${e.observaciones ? `— ${e.observaciones}` : ''}`,
    ]),
  ];
  return f;
});

// --- Generación del PDF 100% en el frontend (html2pdf.js) ---
const OPCIONES_PDF = {
  margin: [15, 15, 15, 15], // 15mm
  image: { type: 'jpeg', quality: 0.98 },
  html2canvas: { scale: 3, useCORS: true, logging: false, backgroundColor: '#0e1726' },
  jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
  pagebreak: { mode: ['avoid-all', 'css', 'legacy'] },
};

/** Datos que se registran del reporte: fecha, formato y el consolidado del trámite. */
function datosReporte() {
  return {
    generado: new Date().toISOString().slice(0, 10),
    formato: 'pdf',
    contenido: filas.value.map(([k, v]) => `${k}: ${v}`).join('\n'),
  };
}

// El reporte es el ÚNICO módulo que se genera solo: en cuanto queda disponible
// (completado el módulo de Defensa) se registra automáticamente y el flujo avanza
// a "Reporte generado", sin que el funcionario tenga que aprobarlo ni pulsar un
// botón. Solo se dispara una vez por módulo; el PDF se sigue descargando a
// demanda desde "Ver reporte" / "Descargar de nuevo".
let autogenerado = false;

watch(
  () => props.enProgreso && !props.completado,
  (disponible) => {
    if (!disponible || autogenerado) return;
    autogenerado = true;
    emit('guardar', datosReporte());
  },
  // `post`: el registro se dispara ya montado el módulo, nunca durante el
  // render del padre (que es quien recibe el evento).
  { immediate: true, flush: 'post' },
);

function nombreReporte() {
  return `reporte_ejecutivo_tesis${props.tramite?.id_tramite ? `_${props.tramite.id_tramite}` : ''}.pdf`;
}

/** Numera las páginas del PDF resultante ("Página 1 de N"). */
function paginar(pdf) {
  const total = pdf.internal.getNumberOfPages();
  const ancho = pdf.internal.pageSize.getWidth();
  const alto = pdf.internal.pageSize.getHeight();
  for (let i = 1; i <= total; i += 1) {
    pdf.setPage(i);
    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(8);
    pdf.setTextColor(148, 163, 184);
    pdf.text(`Página ${i} de ${total}`, ancho - 15, alto - 9, { align: 'right' });
  }
}

/** Rasteriza el área imprimible y devuelve la instancia de jsPDF con paginación aplicada. */
async function producirPdf() {
  if (!contenedorReporte.value) return null;
  const worker = html2pdf().set(OPCIONES_PDF).from(contenedorReporte.value).toPdf();
  const pdf = await worker.get('pdf');
  paginar(pdf);
  return pdf;
}

/** Registra un error de generación sin dejarlo llegar al GlobalErrorBoundary. */
function registrarErrorPdf(error) {
  console.error('[Reporte] No se pudo generar el PDF:', error);
  toastStore.error('No se pudo generar el reporte PDF. Intente nuevamente.');
}

/**
 * Registra el reporte y lo descarga. Es el respaldo manual: el reporte se
 * genera solo al quedar disponible el módulo; este botón solo se usa si eso no
 * llegó a ocurrir.
 */
async function exportarReporte() {
  generando.value = true;
  try {
    const pdf = await producirPdf();
    if (!pdf) return;
    pdf.save(nombreReporte());
    autogenerado = true;
    emit('guardar', datosReporte());
  } catch (error) {
    registrarErrorPdf(error);
  } finally {
    generando.value = false;
  }
}

/** Abre el PDF generado en una pestaña nueva. */
async function visualizar() {
  generando.value = true;
  try {
    const pdf = await producirPdf();
    if (!pdf) return;
    abrirBlob(pdf.output('blob'));
  } catch (error) {
    registrarErrorPdf(error);
  } finally {
    generando.value = false;
  }
}

/** Vuelve a descargar el PDF ya generado, regenerándolo desde el DOM. */
async function descargarActual() {
  generando.value = true;
  try {
    const pdf = await producirPdf();
    if (!pdf) return;
    descargarBlob(pdf.output('blob'), nombreReporte());
  } catch (error) {
    registrarErrorPdf(error);
  } finally {
    generando.value = false;
  }
}

function descargarBlob(blob, nombre) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = nombre;
  a.click();
  URL.revokeObjectURL(url);
}

/** Abre un blob en una pestaña nueva (vista previa/visualización). */
function abrirBlob(blob) {
  const url = URL.createObjectURL(blob);
  window.open(url, '_blank');
  // El visor del navegador necesita más tiempo que la descarga; se libera luego.
  setTimeout(() => URL.revokeObjectURL(url), 60_000);
}

/** Devuelve las clases CSS (scoped) de la insignia según el estado. */
function badgeClass(estado) {
  const mapa = {
    COMPLETADO: 'reporte-badge reporte-badge--verde',
    'EN PROCESO': 'reporte-badge reporte-badge--azul',
    PENDIENTE: 'reporte-badge reporte-badge--gris',
    ASIGNADO: 'reporte-badge reporte-badge--azul',
  };
  return mapa[estado] || mapa.PENDIENTE;
}
</script>

<style scoped>
/* ============================================================
   Área imprimible ("Reporte Ejecutivo de Tesis").
   Paleta institucional: fondo slate oscuro, tarjetas redondeadas,
   acentos verde esmeralda y azul cielo.
   ============================================================ */

.reporte-oculto {
  position: fixed;
  left: -12000px;
  top: 0;
  z-index: -9999;
  pointer-events: none;
}

.reporte-pagina {
  width: 794px; /* ancho A4 @96dpi */
  box-sizing: border-box;
  padding: 26px 30px;
  background: #0e1726;
  color: #e2e8f0;
  font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  font-size: 12px;
  line-height: 1.5;
}

/* -- Encabezado -- */
.reporte-encabezado {
  display: flex;
  align-items: center;
  gap: 16px;
  padding-bottom: 14px;
  border-bottom: 2px solid #1e293b;
}

.reporte-encabezado__logo {
  width: 72px;
  height: 72px;
  flex: 0 0 72px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #334155;
  border: 1px dashed #475569;
  border-radius: 12px;
  color: #94a3b8;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 1px;
  text-align: center;
}

.reporte-encabezado__titulares h1 {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
  letter-spacing: 1px;
  color: #ffffff;
}

.reporte-encabezado__titulares p {
  margin: 2px 0 0;
  font-size: 10px;
  color: #94a3b8;
}

.reporte-encabezado__titulares strong {
  color: #38bdf8;
  font-weight: 600;
}

/* -- Bloques -- */
.reporte-bloque {
  margin-top: 18px;
  padding: 14px 16px;
  background: #111c2e;
  border: 1px solid #1e293b;
  border-radius: 12px;
}

.reporte-bloque h2 {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 10px;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #f1f5f9;
}

.reporte-numero {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 6px;
  background: #10b981;
  color: #052e1d;
  font-size: 10px;
}

.reporte-bloque--estudiante {
  border-color: #10b9814d;
  background: linear-gradient(180deg, #10231f, #0f1a2b);
}

.reporte-bloque--estudiante h2 {
  color: #34d399;
}

/* -- Rejillas de campos -- */
.reporte-grid {
  display: grid;
  gap: 8px;
}

.reporte-grid--2 {
  grid-template-columns: 1fr 1fr;
}

.reporte-campo {
  padding: 10px 12px;
  background: #0e1726;
  border: 1px solid #1e293b;
  border-radius: 10px;
}

.reporte-campo--ancho2 {
  grid-column: 1 / -1;
}

.reporte-campo--acento {
  border-color: #10b98159;
  background: #10b98114;
}

.reporte-campo--nota strong {
  font-size: 16px;
  color: #34d399;
}

.reporte-campo span {
  display: block;
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #64748b;
  margin-bottom: 3px;
}

.reporte-campo strong {
  display: block;
  font-size: 12px;
  font-weight: 700;
  color: #e2e8f0;
  word-break: break-word;
}

.reporte-campo--acento strong {
  color: #34d399;
}

/* -- KPIs -- */
.reporte-kpis {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-top: 18px;
}

.reporte-kpi {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 92px;
  padding: 14px;
  background: #111c2e;
  border: 1px solid #1e293b;
  border-radius: 12px;
}

.reporte-kpi--enfasis {
  border-color: #38bdf8;
  background: linear-gradient(160deg, #082f49, #0b2440);
}

.reporte-kpi__label {
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #94a3b8;
}

.reporte-kpi--enfasis .reporte-kpi__label {
  color: #7dd3fc;
}

.reporte-kpi__valor {
  font-size: 26px;
  font-weight: 800;
  color: #ffffff;
  line-height: 1.1;
}

.reporte-kpi--enfasis .reporte-kpi__valor {
  color: #7dd3fc;
}

.reporte-kpi__meta {
  font-size: 9px;
  color: #64748b;
}

.reporte-kpi__badge {
  display: flex;
}

.reporte-progreso {
  height: 7px;
  margin-top: 6px;
  background: #0b1220;
  border-radius: 999px;
  overflow: hidden;
}

.reporte-progreso span {
  display: block;
  height: 100%;
  background: linear-gradient(90deg, #10b981, #38bdf8);
  border-radius: 999px;
}

/* -- Insignias de estado -- */
.reporte-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.5px;
}

.reporte-badge--verde {
  background: #10b9811f;
  border: 1px solid #10b98180;
  color: #34d399;
}

.reporte-badge--azul {
  background: #38bdf81f;
  border: 1px solid #38bdf880;
  color: #7dd3fc;
}

.reporte-badge--gris {
  background: #64748b1f;
  border: 1px solid #64748b80;
  color: #94a3b8;
}

/* -- Tablas -- */
.reporte-tabla {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #1e293b;
  border-radius: 10px;
  overflow: hidden;
}

.reporte-tabla th {
  padding: 8px 12px;
  text-align: left;
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #94a3b8;
  background: #0b1220;
  border-bottom: 1px solid #1e293b;
}

.reporte-tabla td {
  padding: 9px 12px;
  border-bottom: 1px solid #ffffff12;
  color: #e2e8f0;
}

.reporte-tabla tr:last-child td {
  border-bottom: 0;
}

.reporte-tabla__archivo {
  color: #7dd3fc !important;
  font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
  font-size: 10px;
}

/* -- Pie de página -- */
.reporte-pie {
  margin-top: 20px;
  padding-top: 12px;
  border-top: 2px solid #1e293b;
  text-align: center;
  font-size: 9px;
  color: #64748b;
}

.reporte-pie p {
  margin: 0;
}

.reporte-pie__marca {
  margin-top: 4px !important;
  font-weight: 800;
  letter-spacing: 1.5px;
  color: #475569;
}
</style>