<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-blue-800 text-white p-4 flex justify-between items-center">
      <h1 class="text-xl font-bold">Panel de Administrador</h1>
      <div class="flex items-center gap-4">
        <span>Hola, {{ authStore.user?.nombres || 'Administrador' }}</span>
        <button @click="logout" class="bg-red-500 px-4 py-2 rounded hover:bg-red-600">Cerrar Sesión</button>
      </div>
    </nav>

    <div class="p-8">
      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold text-gray-800">Gestión de Usuarios (Matriz de Actores)</h2>
          <button @click="showModal = true" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
            + Dar de Alta Usuario
          </button>
        </div>

        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-200">
              <th class="p-3 border">CI</th>
              <th class="p-3 border">Nombre Completo</th>
              <th class="p-3 border">Email</th>
              <th class="p-3 border">Rol</th>
              <th class="p-3 border">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id_usuario" class="hover:bg-gray-50">
              <td class="p-3 border">{{ user.ci }}</td>
              <td class="p-3 border">{{ user.nombres }} {{ user.apellidos }}</td>
              <td class="p-3 border">{{ user.email }}</td>
              <td class="p-3 border">
                <span class="px-2 py-1 rounded text-xs text-white" :class="getRoleColor(user.rol)">
                  {{ user.rol ? user.rol.toUpperCase() : 'SIN ROL' }}
                </span>
              </td>
              <td class="p-3 border">
                <button @click="deleteUser(user.id_usuario)" class="text-red-500 hover:text-red-700">🗑️ Baja</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modal de Alta -->
      <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg w-96">
          <h3 class="text-xl font-bold mb-4">Alta de Nuevo Usuario</h3>
          <form @submit.prevent="createUser" class="space-y-2">
            <input v-model="newUser.ci" placeholder="CI" class="w-full border p-2 rounded" required>
            <input v-model="newUser.nombres" placeholder="Nombres" class="w-full border p-2 rounded" required>
            <input v-model="newUser.apellidos" placeholder="Apellidos" class="w-full border p-2 rounded" required>
            <input v-model="newUser.email" placeholder="Email" type="email" class="w-full border p-2 rounded" required>
            <input v-model="newUser.telefono" placeholder="Teléfono (Ej: 59170000000)" class="w-full border p-2 rounded">
            <select v-model="newUser.rol" class="w-full border p-2 rounded" required>
              <option value="" disabled>Seleccionar Rol</option>
              <option v-for="rol in roles" :key="rol" :value="rol">{{ rol.toUpperCase() }}</option>
            </select>
            <input v-model="newUser.password" placeholder="Contraseña" type="password" class="w-full border p-2 rounded" required>
            
            <div class="flex justify-end gap-2 mt-4">
              <button type="button" @click="showModal = false" class="bg-gray-300 px-4 py-2 rounded">Cancelar</button>
              <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';

const authStore = useAuthStore();
const users = ref([]);
const showModal = ref(false);
const roles = ['estudiante', 'docente', 'kardex', 'secretaria', 'direccion', 'concejo', 'admin'];

const newUser = ref({ ci: '', nombres: '', apellidos: '', email: '', telefono: '', rol: 'estudiante', password: '' });

onMounted(() => {
  fetchUsers();
});

const fetchUsers = async () => {
  try {
    const { data } = await api.get('/usuarios');
    users.value = data;
  } catch (error) {
    console.error('Error al cargar la matriz de usuarios:', error);
    if (error.response?.status === 401) {
      authStore.logout();
      window.location.href = '/login';
    }
  }
};

const createUser = async () => {
  try {
    await api.post('/usuarios', newUser.value);
    showModal.value = false;
    newUser.value = { ci: '', nombres: '', apellidos: '', email: '', telefono: '', rol: 'estudiante', password: '' };
    await fetchUsers();
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || 'Verifica los datos'));
  }
};

const deleteUser = async (id) => {
  if (confirm('¿Dar de baja a este usuario?')) {
    try {
      await api.delete(`/usuarios/${id}`);
      await fetchUsers();
    } catch (error) {
      alert('No se pudo dar de baja al usuario.');
    }
  }
};

const logout = () => {
  authStore.logout();
  window.location.href = '/login';
};

const getRoleColor = (rol) => ({
  admin: 'bg-red-500', estudiante: 'bg-blue-500', docente: 'bg-green-500',
  kardex: 'bg-purple-500', secretaria: 'bg-yellow-500', direccion: 'bg-indigo-500', concejo: 'bg-pink-500'
}[rol] || 'bg-gray-500');
</script>