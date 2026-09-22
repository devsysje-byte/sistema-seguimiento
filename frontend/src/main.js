import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

import './assets/main.css'

// Crea la instancia raíz de la aplicación y registra Pinia (estado global) y
// Vue Router antes de montar el componente raíz en el elemento #app.
const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
