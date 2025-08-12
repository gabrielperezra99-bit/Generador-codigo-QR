class API {
    constructor() {
        // Configuración para desarrollo local
        this.baseURL = 'http://localhost/planos/api';
        this.token = localStorage.getItem('token');
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        if (this.token) {
            config.headers['Authorization'] = `Bearer ${this.token}`;
        }

        try {
            const response = await fetch(url, config);
            
            // Si el token es inválido, limpiar y redirigir
            if (response.status === 401) {
                this.clearAuth();
                return null;
            }
            
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error en la petición');
            }

            return data;
        } catch (error) {
            console.error('Error en API:', error);
            throw error;
        }
    }

    // Métodos de autenticación
    async login(email, password) {
        try {
            const response = await this.request('/auth/login.php', {
                method: 'POST',
                body: JSON.stringify({ email, password })
            });
            
            if (response && response.token) {
                this.token = response.token;
                localStorage.setItem('token', this.token);
                localStorage.setItem('user', JSON.stringify(response.user));
                return { success: true, data: response };
            }
            
            return { success: false, message: 'Credenciales inválidas' };
        } catch (error) {
            return { success: false, message: error.message || 'Error de conexión' };
        }
    }

    async register(userData) {
        try {
            const response = await this.request('/auth/register.php', {
                method: 'POST',
                body: JSON.stringify(userData)
            });
            
            if (response && response.token) {
                this.token = response.token;
                localStorage.setItem('token', this.token);
                localStorage.setItem('user', JSON.stringify(response.user));
                return { success: true, data: response };
            }
            
            return { success: false, message: 'Error en el registro' };
        } catch (error) {
            return { success: false, message: error.message || 'Error de conexión' };
        }
    }

    clearAuth() {
        this.token = null;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
    }

    logout() {
        this.clearAuth();
        window.location.href = 'login.html';
    }

    // Verificar si el usuario está autenticado
    isAuthenticated() {
        return !!this.token && !!localStorage.getItem('user');
    }

    // Obtener información del usuario
    getUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    }

    // Métodos de planos
    async getPlanos() {
        return await this.request('/planos/read.php');
    }

    async createPlano(formData) {
        const response = await fetch(`${this.baseURL}/planos/create.php`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`
            },
            body: formData // FormData se envía sin Content-Type header
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    async deletePlano(id) {
        return await this.request('/planos/delete.php', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
    }

    async searchPlanos(filters) {
        const params = new URLSearchParams();
        
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                params.append(key, filters[key]);
            }
        });

        return await this.request(`/planos/search.php?${params.toString()}`);
    }

    // Método para toggle favorito
    async toggleFavorito(planoId) {
        return await this.request('/planos/toggle_favorito.php', {
            method: 'POST',
            body: JSON.stringify({ plano_id: planoId })
        });
    }
}

// Instancia global de la API
const api = new API();