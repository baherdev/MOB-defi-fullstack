// src/plugins/vuetify.ts
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'

export default createVuetify({
    components,
    directives,
    theme: {
        defaultTheme: 'light',
        themes: {
            light: {
                colors: {
                    primary: '#002E5A',      // Bleu officiel MOB
                    secondary: '#BF252B',     // Rouge officiel MOB (croix suisse)
                    accent: '#003DA5',        // Bleu accent
                    error: '#FF5252',
                    info: '#2196F3',
                    success: '#4CAF50',
                    warning: '#FFC107',
                },
            },
            dark: {
                colors: {
                    primary: '#003DA5',       // Bleu plus clair pour le mode sombre
                    secondary: '#BF252B',     // Rouge MOB
                },
            },
        },
    },
})
