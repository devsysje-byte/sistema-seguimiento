import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

import './assets/main.css' // o './assets/main.css' dependiendo de dónde lo hayas creado

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
