<template>
  <AppShell title="Panel de Administración" subtitle="Gestión de usuarios y roles del sistema">
    <div id="seccion-estadisticas" class="scroll-mt-28">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <StatCard label="Total de Usuarios" :value="users.length" icon="users" tone="amber" />
        <StatCard label="Estudiantes" :value="porRol('estudiante')" icon="graduation" tone="rose" />
        <StatCard label="Docentes" :value="porRol('docente')" icon="user-check" tone="orange" />
        <StatCard label="Personal Académico" :value="users.length - porRol('estudiante') - porRol('docente')" icon="shield" tone="amber" />
      </div>

      <EstadisticasModalidades />
    </div>

    <div id="seccion-tabla" class="scroll-mt-28 card overflow-hidden">
          <div class="flex flex-wrap items-center gap-3 p-5 border-b border-stone-100">
            <div>
              <h2 class="text-lg font-bold text-stone-900">Matriz de Actores</h2>
              <p class="text-sm text-stone-500">Usuarios registrados y habilitados en la plataforma.</p>
            </div>
            <div class="ml-auto w-full md:w-auto flex flex-wrap gap-3 items-center">
              <div class="relative flex-1 md:flex-none md:min-w-52">
                <span class="absolute inset-y-0 left-3 flex items-center text-stone-400">
                  <AppIcon name="search" :size="17" />
                </span>
                <input v-model="buscar" class="input pl-9 md:w-52" placeholder="Buscar usuario..." />
              </div>
              <select v-model="filtroRol" class="input md:w-44">
                <option value="">Todos los roles</option>
                <option v-for="rol in roles" :key="rol" :value="rol">{{ rolLabel(rol) }}</option>
              </select>
              <button class="btn-primary px-4" @click="showModalCrear = true">
                <AppIcon name="plus" :size="16" />
                Nuevo Usuario
              </button>
            </div>
          </div>

          <div v-if="cargando" class="flex items-center justify-center gap-2 py-16 text-stone-400">
            <AppIcon name="loader" :size="20" class="animate-spin" />
            Cargando usuarios...
          </div>

          <template v-else>
            <div v-if="usuariosFiltrados.length === 0" class="p-6">
              <EmptyState icon="users" title="Sin resultados" message="No se encontraron usuarios con los criterios actuales.">
                <button class="btn-ghost" @click="buscar = ''; filtroRol = ''">Limpiar filtros</button>
              </EmptyState>
            </div>

            <div v-else class="overflow-x-auto">
              <table class="w-full min-w-[640px] text-left">
                <thead class="bg-stone-50 border-b border-stone-200">
                  <tr>
                    <th class="th">Usuario</th>
                    <th class="th">Email</th>
                    <th class="th">Rol</th>
                    <th class="th text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                  <tr
                    v-for="user in usuariosFiltrados"
                    :key="user.id_usuario"
                    class="hover:bg-amber-50/50 transition cursor-pointer"
                    :class="usuarioSeleccionado?.id_usuario === user.id_usuario ? 'bg-amber-50 ring-1 ring-amber-200' : ''"
                    @click="seleccionarUsuario(user)"
                  >
                    <td class="td">
                      <div class="flex items-center gap-3">
                        <Avatar :nombres="user.nombres" :apellidos="user.apellidos" size="10" />
                        <div>
                          <p class="font-semibold text-stone-800">{{ user.nombres }} {{ user.apellidos }}</p>
                          <p class="text-xs text-stone-400">CI: {{ user.ci }} · {{ user.telefono || 'Sin teléfono' }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="td">
                      <span class="inline-flex items-center gap-1.5 text-stone-600">
                        <AppIcon name="mail" :size="14" class="text-stone-400" />
                        {{ user.email }}
                      </span>
                    </td>
                    <td class="td">
                      <RoleBadge :rol="user.rol" />
                    </td>
                    <td class="td text-right">
                      <div class="flex items-center justify-end gap-2">
                        <button
                          class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-600 hover:text-amber-800 transition px-2 py-1 rounded-lg hover:bg-amber-50"
                          @click.stop="editarUsuario(user)"
                        >
                          <AppIcon name="pencil" :size="14" />
                          Editar
                        </button>
                        <button class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-500 hover:text-rose-700 transition px-2 py-1 rounded-lg hover:bg-rose-50" @click.stop="eliminarUsuario(user)">
                          <AppIcon name="trash" :size="15" />
                          Dar de baja
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
        </div>
      </template>
    </div>

    <UiModal v-model="showModalCrear" title="Dar de Alta un Nuevo Usuario" max-width="500px">
      <form @submit.prevent="crearUsuario" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">CI</label>
            <input v-model="nuevoUsuario.ci" class="input" placeholder="1234567" required>
          </div>
          <div>
            <label class="label">Teléfono</label>
            <input v-model="nuevoUsuario.telefono" class="input" placeholder="59170000000">
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Nombres</label>
            <input v-model="nuevoUsuario.nombres" class="input" placeholder="Nombre(s)" required>
          </div>
          <div>
            <label class="label">Apellidos</label>
            <input v-model="nuevoUsuario.apellidos" class="input" placeholder="Apellido(s)" required>
          </div>
        </div>
        <div>
          <label class="label">Email</label>
          <input v-model="nuevoUsuario.email" type="email" class="input" placeholder="correo@upea.bo" required>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Rol</label>
            <select v-model="nuevoUsuario.rol" class="input" required>
              <option v-for="rol in roles" :key="rol" :value="rol">{{ rolLabel(rol) }}</option>
            </select>
          </div>
          <div>
            <label class="label">Contraseña</label>
            <input v-model="nuevoUsuario.password" type="" class="input" placeholder="Mín. 8 caracteres" required>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-ghost px-4 py-2.5" @click="showModalCrear = false">Cancelar</button>
          <button type="submit" class="btn-primary px-4 py-2.5">
            <AppIcon name="plus" :size="16" />
            Dar de Alta
          </button>
        </div>
      </form>
    </UiModal>

    <UiModal v-model="showModalActualizar" title="Actualizar Usuario" max-width="560px">
      <form @submit.prevent="actualizarUsuario" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">CI</label>
            <input v-model="editarForm.ci" class="input" placeholder="1234567" required>
          </div>
          <div>
            <label class="label">Teléfono</label>
            <input v-model="editarForm.telefono" class="input" placeholder="59170000000">
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Nombres</label>
            <input v-model="editarForm.nombres" class="input" placeholder="Nombre(s)" required>
          </div>
          <div>
            <label class="label">Apellidos</label>
            <input v-model="editarForm.apellidos" class="input" placeholder="Apellido(s)" required>
          </div>
        </div>
        <div>
          <label class="label">Email</label>
          <input v-model="editarForm.email" type="email" class="input" placeholder="correo@upea.bo" required>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Rol</label>
            <select v-model="editarForm.rol" class="input" required>
              <option v-for="rol in roles" :key="rol" :value="rol">{{ rolLabel(rol) }}</option>
            </select>
          </div>
          <div>
            <label class="label">Contraseña</label>
            <input v-model="editarForm.password" type="" class="input" placeholder="Mín. 8 caracteres" autocomplete="new-password">
          </div>
          <p class="sm:col-span-2 text-xs text-stone-400">Déjala vacía para mantener la contraseña actual o escríbela para reestablecerla.</p>
        </div>

        <div v-if="editarForm.rol === 'estudiante'" class="border-t border-stone-100 pt-4">
          <h4 class="text-sm font-bold text-stone-700 mb-3">Datos del Estudiante</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="label">Código Universitario</label>
              <input v-model="editarForm.codigo_universitario" class="input" placeholder="ej. 200109207">
            </div>
            <div>
              <label class="label">Plan de Estudios</label>
              <input v-model="editarForm.plan_estudios" class="input" placeholder="ej. 2016">
            </div>
            <div>
              <label class="label">Fecha Conclusión de Plan</label>
              <input v-model="editarForm.fecha_conclusion_plan" type="date" class="input">
            </div>
            <div>
              <label class="label">Promedio Global</label>
              <input v-model="editarForm.promedio_global" type="number" step="0.01" min="0" max="100" class="input" placeholder="0.00 - 100">
            </div>
            <div>
              <label class="label">Estado</label>
              <select v-model="editarForm.estado_estudiante" class="input">
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
              </select>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-ghost px-4 py-2.5" @click="showModalActualizar = false">Cancelar</button>
          <button type="submit" class="btn-warm px-4 py-2.5">
            <AppIcon name="check" :size="16" />
            Actualizar
          </button>
        </div>
      </form>
    </UiModal>
  </AppShell>
</template>

<script setup>
// Vista de administración de usuarios.
// Muestra una matriz de actores con búsqueda y filtro por rol, permite crear,
// editar (incluidos datos de estudiante) y dar de baja usuarios mediante la API.
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/modules/auth';
import { usuariosService } from '../services/usuarios';
import { rolLabel, ROLE_LABELS } from '@/core/roles';
import { useToastStore } from '@/core/stores/toast';
import { AppShell } from '@/modules/layout';
import AppIcon from '@/ui/AppIcon.vue';
import Avatar from '@/ui/Avatar.vue';
import RoleBadge from '@/ui/RoleBadge.vue';
import StatCard from '@/ui/StatCard.vue';
import { EstadisticasModalidades } from '@/modules/tramites';
import UiModal from '@/ui/UiModal.vue';
import EmptyState from '@/ui/EmptyState.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();

// Estado de la matriz de usuarios y de la UI.
const users = ref([]);              // Lista completa de usuarios activos.
const cargando = ref(false);        // true mientras se cargan los usuarios.
const showModalCrear = ref(false);  // Modal de alta de usuario.
const showModalActualizar = ref(false);
const buscar = ref('');             // Texto de búsqueda.
const filtroRol = ref('');          // Filtro por rol.
const roles = Object.keys(ROLE_LABELS);
const usuarioSeleccionado = ref(null); // Fila seleccionada en la tabla.

// Formularios de creación y edición de usuario.
const nuevoUsuario = ref({ ci: '', nombres: '', apellidos: '', email: '', telefono: '', rol: 'estudiante', password: '' });
const editarForm = ref({
  id_usuario: null, ci: '', nombres: '', apellidos: '', email: '', telefono: '', rol: 'estudiante',
  password: '', codigo_universitario: '', plan_estudios: '', fecha_conclusion_plan: '', promedio_global: '', estado_estudiante: 'activo',
});

/** Restablece el formulario de edición a sus valores vacíos por defecto. */
const editarFormLimpiar = () => {
  editarForm.value = {
    id_usuario: null, ci: '', nombres: '', apellidos: '', email: '', telefono: '', rol: 'estudiante',
    password: '', codigo_universitario: '', plan_estudios: '', fecha_conclusion_plan: '', promedio_global: '', estado_estudiante: 'activo',
  };
};

onMounted(() => {
  fetchUsers();
});

// Filtra los usuarios combinando el texto de búsqueda y el rol seleccionado.
const usuariosFiltrados = computed(() => {
  const q = buscar.value.toLowerCase().trim();
  return users.value.filter((u) => {
    const matchRol = !filtroRol.value || u.rol === filtroRol.value;
    const matchQ = !q
      || `${u.nombres} ${u.apellidos}`.toLowerCase().includes(q)
      || u.email.toLowerCase().includes(q)
      || `${u.ci}`.includes(q);
    return matchRol && matchQ;
  });
});

/** Cuenta cuántos usuarios tienen el rol indicado. */
const porRol = (rol) => users.value.filter((u) => u.rol === rol).length;

/** Carga la matriz de usuarios desde GET /api/usuarios. Maneja sesiones 401. */
const fetchUsers = async () => {
  cargando.value = true;
  try {
    const { data } = await usuariosService.index();
    users.value = data.data;
  } catch (error) {
    console.error('Error al cargar la matriz de usuarios:', error);
    if (error.response?.status === 401) {
      authStore.logout();
      window.location.href = '/login';
    }
  } finally {
    cargando.value = false;
  }
};

/** Selecciona una fila de la tabla. */
const seleccionarUsuario = (user) => {
  usuarioSeleccionado.value = user;
};

/** Prepara el formulario de edición con los datos del usuario (y su estudiante). */
const editarUsuario = (user) => {
  usuarioSeleccionado.value = user;
  editarFormLimpiar();
  editarForm.value = {
    ...user,
    password: '',
    codigo_universitario: user.estudiante?.codigo_universitario || '',
    plan_estudios: user.estudiante?.plan_estudios || '',
    fecha_conclusion_plan: user.estudiante?.fecha_conclusion_plan || '',
    promedio_global: user.estudiante?.promedio_global ?? '',
    estado_estudiante: user.estudiante?.estado || 'activo',
  };
  showModalActualizar.value = true;
};

/** Abre el modal de edición para el usuario seleccionado en la tabla. */
const abrirActualizar = () => {
  if (usuarioSeleccionado.value) {
    editarUsuario(usuarioSeleccionado.value);
  }
};

/** Crea un usuario vía POST /api/usuarios y refresca la matriz. */
const crearUsuario = async () => {
  try {
    await usuariosService.crear(nuevoUsuario.value);
    showModalCrear.value = false;
    toastStore.success(`Usuario ${nuevoUsuario.value.nombres} ${nuevoUsuario.value.apellidos} dado de alta correctamente.`);
    nuevoUsuario.value = { ci: '', nombres: '', apellidos: '', email: '', telefono: '', rol: 'estudiante', password: '' };
    await fetchUsers();
  } catch (error) {
    toastStore.error('Error: ' + (error.response?.data?.message || 'Verifica los datos'));
  }
};

/** Actualiza un usuario (y su perfil de estudiante si aplica) vía PUT /api/usuarios/{id}. */
const actualizarUsuario = async () => {
  try {
    const payload = {
      ci: editarForm.value.ci,
      nombres: editarForm.value.nombres,
      apellidos: editarForm.value.apellidos,
      email: editarForm.value.email,
      telefono: editarForm.value.telefono,
      rol: editarForm.value.rol,
    };
    // Solo se envía contraseña si el admin escribió una nueva.
    if (editarForm.value.password) payload.password = editarForm.value.password;

    // Si el rol es estudiante, adjunta el perfil académico.
    if (payload.rol === 'estudiante') {
      payload.estudiante = {
        codigo_universitario: editarForm.value.codigo_universitario,
        plan_estudios: editarForm.value.plan_estudios,
        fecha_conclusion_plan: editarForm.value.fecha_conclusion_plan,
        promedio_global: editarForm.value.promedio_global,
        estado: editarForm.value.estado_estudiante,
      };
    }

    await usuariosService.actualizar(editarForm.value.id_usuario, payload);
    showModalActualizar.value = false;
    toastStore.success(`Datos de ${editarForm.value.nombres} ${editarForm.value.apellidos} actualizados correctamente.`);
    await fetchUsers();
    usuarioSeleccionado.value = null;
  } catch (error) {
    toastStore.error('Error: ' + (error.response?.data?.message || 'No se pudo actualizar'));
  }
};

/** Da de baja lógica a un usuario con confirmación previa vía DELETE /api/usuarios/{id}. */
const eliminarUsuario = async (user) => {
  if (!confirm(`¿Dar de baja a ${user.nombres} ${user.apellidos}? Esta acción deshabilita su acceso.`)) return;
  try {
    await usuariosService.eliminar(user.id_usuario);
    await fetchUsers();
  } catch (error) {
    toastStore.error('No se pudo dar de baja al usuario.');
  }
};
</script>
