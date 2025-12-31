// SCAMFOOD - Aplicación Principal
class ScamFoodApp {
    constructor() {
        this.baseUrl = 'http://localhost/SCAMFOOD/';
        this.userData = null;
        this.allergens = [];
        this.init();
    }

    init() {
        this.checkAuth();
        this.loadUserData();
        this.setupEventListeners();
    }

    checkAuth() {
        $.ajax({
            url: this.baseUrl + 'php/check_auth.php',
            method: 'GET',
            success: (response) => {
                const data = JSON.parse(response);
                if (data.loggedIn) {
                    this.userData = data.user;
                    this.updateUIForLoggedUser();
                } else {
                    this.redirectToLogin();
                }
            },
            error: () => {
                this.redirectToLogin();
            }
        });
    }

    loadUserData() {
        if (!this.userData) return;
        
        $.ajax({
            url: this.baseUrl + 'php/get_user_data.php',
            method: 'GET',
            data: { user_id: this.userData.id },
            success: (response) => {
                const data = JSON.parse(response);
                if (data.success) {
                    this.allergens = data.allergens;
                    this.updateAllergensDisplay();
                }
            }
        });
    }

    updateUIForLoggedUser() {
        // Actualizar nombre de usuario en todas las páginas
        $('.user-name').text(this.userData.name);
        $('#userName').text(this.userData.name);
        
        // Mostrar elementos específicos para usuarios logueados
        $('.user-only').show();
        $('.guest-only').hide();
    }

    updateAllergensDisplay() {
        const container = $('#myAllergensList');
        if (!container.length) return;
        
        if (this.allergens.length > 0) {
            let html = '<div class="d-flex flex-wrap gap-2">';
            this.allergens.forEach(allergen => {
                html += `
                    <span class="badge bg-danger p-2">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        ${allergen}
                    </span>
                `;
            });
            html += '</div>';
            container.html(html);
        } else {
            container.html(`
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> 
                    No has configurado tus alérgenos. 
                    <a href="perfil.html" class="alert-link">Configurar ahora</a>
                </div>
            `);
        }
    }

    setupEventListeners() {
        // Logout
        $(document).on('click', '#logoutBtn', (e) => {
            e.preventDefault();
            this.logout();
        });

        // Scanner
        $(document).on('click', '.scan-btn', () => {
            this.openScanner();
        });
    }

    logout() {
        $.ajax({
            url: this.baseUrl + 'php/logout.php',
            method: 'GET',
            success: () => {
                window.location.href = 'login.html';
            }
        });
    }

    openScanner() {
        window.location.href = 'escaner.html';
    }

    redirectToLogin() {
        // Solo redirigir si no está en la página de login/register
        if (!window.location.pathname.includes('login.html') && 
            !window.location.pathname.includes('register.html') &&
            !window.location.pathname.includes('index.html')) {
            window.location.href = 'login.html';
        }
    }

    // Método para analizar producto
    analyzeProduct(barcode) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.baseUrl + 'php/get_product.php',
                method: 'POST',
                data: { barcode: barcode },
                success: (response) => {
                    const data = JSON.parse(response);
                    if (data.success) {
                        resolve(data);
                    } else {
                        reject(data.message);
                    }
                },
                error: () => {
                    reject('Error de conexión');
                }
            });
        });
    }
}

// Inicializar la aplicación cuando el DOM esté listo
$(document).ready(function() {
    window.scamFoodApp = new ScamFoodApp();
});