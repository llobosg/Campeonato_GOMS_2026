/**
 * app.js - JavaScript Principal Campeonato GOMS 2026
 * Ubicación: public/assets/js/app.js
 * Maneja: Tabs, Modales, API Calls, Animaciones, Toasts
 */

// ============================================
// CONFIGURACIÓN GLOBAL
// ============================================
const BASE_URL = window.location.origin + (window.location.pathname.includes('/campeonato') ? '/campeonato%20goms%202026/public' : '');
let currentFixtureId = null;
let currentPartidoData = null;

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    initTabs();
    initModalEvents();
    checkFlashMessages();
});

// ============================================
// TABS DE FECHAS
// ============================================
function initTabs() {
    const tabs = document.querySelectorAll('.fecha-tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remover active de todos
            document.querySelectorAll('.fecha-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.fecha-content').forEach(c => c.classList.remove('active'));
            
            // Activar el clickeado
            this.classList.add('active');
            const fechaNum = this.dataset.fecha;
            const content = document.getElementById(`fecha-${fechaNum}`);
            if (content) {
                content.classList.add('active');
                animateTabSwitch(content);
            }
        });
    });
}

// ============================================
// ANIMACIÓN DE SWITCH ENTRE FECHAS (opcional)
// ============================================
function animateTabSwitch(element) {
    element.style.opacity = '0';
    element.style.transform = 'translateY(10px)';
    
    setTimeout(() => {
        element.style.transition = 'all 0.3s ease';
        element.style.opacity = '1';
        element.style.transform = 'translateY(0)';
    }, 50);
}

// ============================================
// MODAL DE RESULTADO
// ============================================
function openResultadoModal(fixtureId) {
    currentFixtureId = fixtureId;
    const modal = document.getElementById('resultadoModal');
    
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevenir scroll
        
        // Resetear pasos
        showPasswordStep();
        
        // Animación de entrada
        modal.querySelector('.modal-content').style.animation = 'none';
        setTimeout(() => {
            modal.querySelector('.modal-content').style.animation = 'slideUp 0.3s ease';
        }, 10);
    }
}

function closeResultadoModal() {
    const modal = document.getElementById('resultadoModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = ''; // Restaurar scroll
        currentFixtureId = null;
        currentPartidoData = null;
    }
}

function showPasswordStep() {
    const pasoPassword = document.getElementById('paso-password');
    const pasoGoles = document.getElementById('paso-goles');
    
    if (pasoPassword) pasoPassword.style.display = 'block';
    if (pasoGoles) pasoGoles.style.display = 'none';
    
    // Limpiar campos
    const passwordInput = document.getElementById('adminPassword');
    const error_msg = document.getElementById('passwordError');
    if (passwordInput) passwordInput.value = '';
    if (error_msg) error_msg.style.display = 'none';
}

function showGolesStep() {
    const pasoPassword = document.getElementById('paso-password');
    const pasoGoles = document.getElementById('paso-goles');
    
    if (pasoPassword) pasoPassword.style.display = 'none';
    if (pasoGoles) pasoGoles.style.display = 'block';
}

// ============================================
// VERIFICAR CONTRASEÑA ADMIN
// ============================================
async function verificarPassword() {
    const passwordInput = document.getElementById('adminPassword');
    const errorMsg = document.getElementById('passwordError');
    const password = passwordInput ? passwordInput.value.trim() : '';
    
    if (!password) {
        if (errorMsg) {
            errorMsg.textContent = '️ Ingrese una contraseña';
            errorMsg.style.display = 'block';
        }
        return;
    }
    
    try {
        showToast('⏳ Verificando...', 'info');
        
        const response = await fetch(`${BASE_URL}/api/resultado/verificar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ password: password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Contraseña correcta', 'success');
            showGolesStep();
            await cargarJugadoresPartido(currentFixtureId);
        } else {
            if (errorMsg) {
                errorMsg.textContent = '❌ Contraseña incorrecta';
                errorMsg.style.display = 'block';
            }
            showToast('❌ Contraseña incorrecta', 'error');
        }
    } catch (error) {
        console.error('Error verificando password:', error);
        showToast('❌ Error de conexión', 'error');
    }
}

// Permitir Enter para verificar password
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.target.id === 'adminPassword') {
        verificarPassword();
    }
});

// ============================================
// CARGAR DATOS DEL PARTIDO
// ============================================
async function cargarJugadoresPartido(fixtureId) {
    if (!fixtureId) return;
    
    try {
        showToast('⏳ Cargando datos del partido...', 'info');
        
        const response = await fetch(`${BASE_URL}/api/fixture/${fixtureId}`);
        const data = await response.json();
        
        if (data.success) {
            currentPartidoData = data.data;
            
            // Actualizar UI con datos del partido
            actualizarInfoPartido(currentPartidoData);
            
            // Cargar jugadores de ambos equipos
            await Promise.all([
                cargarListaJugadores(currentPartidoData.equipo_a, 'A'),
                cargarListaJugadores(currentPartidoData.equipo_b, 'B')
            ]);
            
            showToast('✅ Datos cargados', 'success');
        } else {
            showToast('❌ Error al cargar datos', 'error');
        }
    } catch (error) {
        console.error('Error cargando partido:', error);
        showToast(' Error de conexión', 'error');
    }
}

// ============================================
// ACTUALIZAR INFORMACIÓN DEL PARTIDO EN EL MODAL
// ============================================
function actualizarInfoPartido(partido) {
    // Título del modal
    const titulo = document.getElementById('modalPartidoTitulo');
    if (titulo) {
        const fechaFormateada = formatDate(partido.fecha);
        const hora = partido.hora.substring(0, 5);
        titulo.textContent = `Fecha ${partido.nro_fecha} - ${fechaFormateada} ${hora}`;
    }
    
    // Equipos en preview
    const previewEquipoA = document.getElementById('previewEquipoA');
    const previewEquipoB = document.getElementById('previewEquipoB');
    if (previewEquipoA) previewEquipoA.textContent = partido.nombre_equipo_a;
    if (previewEquipoB) previewEquipoB.textContent = partido.nombre_equipo_b;
    
    // Títulos de secciones
    const tituloEquipoA = document.getElementById('tituloEquipoA');
    const tituloEquipoB = document.getElementById('tituloEquipoB');
    if (tituloEquipoA) tituloEquipoA.textContent = partido.nombre_equipo_a;
    if (tituloEquipoB) tituloEquipoB.textContent = partido.nombre_equipo_b;
    
    // Resetear marcadores
    const previewGolesA = document.getElementById('previewGolesA');
    const previewGolesB = document.getElementById('previewGolesB');
    if (previewGolesA) previewGolesA.textContent = '0';
    if (previewGolesB) previewGolesB.textContent = '0';
}

// ============================================
// CARGAR LISTA DE JUGADORES CON CONTROLES +/-
// ============================================
async function cargarListaJugadores(equipoId, lado) {
    const container = document.getElementById(`listaJugadores${lado}`);
    if (!container) return;
    
    try {
        const response = await fetch(`${BASE_URL}/api/jugadores?equipo=${equipoId}`);
        const data = await response.json();
        
        container.innerHTML = '';
        
        if (data.success && data.data && data.data.length > 0) {
            data.data.forEach(jugador => {
                const div = document.createElement('div');
                div.className = 'jugador-gol-item';
                div.dataset.jugadorId = jugador.id_jugador;
                
                div.innerHTML = `
                    <span class="jugador-nombre">${escapeHtml(jugador.nombre)}</span>
                    <div class="gol-controls">
                        <button class="btn-minus" onclick="restarGol(${jugador.id_jugador}, '${lado}')" aria-label="Restar gol">−</button>
                        <span class="gol-count" id="gol-${jugador.id_jugador}" role="status">0</span>
                        <button class="btn-plus" onclick="sumarGol(${jugador.id_jugador}, '${lado}')" aria-label="Sumar gol">+</button>
                    </div>
                `;
                
                container.appendChild(div);
            });
        } else {
            container.innerHTML = '<p class="no-jugadores">No hay jugadores registrados en este equipo</p>';
        }
    } catch (error) {
        console.error(`Error cargando jugadores equipo ${lado}:`, error);
        container.innerHTML = '<p class="error-message">Error al cargar jugadores</p>';
    }
}

// ============================================
// CONTROLES DE GOLES +/-
// ============================================
function sumarGol(jugadorId, lado) {
    const element = document.getElementById(`gol-${jugadorId}`);
    if (!element) return;
    
    let count = parseInt(element.textContent) || 0;
    element.textContent = count + 1;
    
    // Animación visual
    animateNumber(element);
    
    // Actualizar marcador preview
    actualizarMarcadorPreview();
    
    // Feedback táctil (vibración en móviles)
    if (navigator.vibrate) {
        navigator.vibrate(50);
    }
}

// ============================================
// RESTA DE GOLES
// ============================================
function restarGol(jugadorId, lado) {
    const element = document.getElementById(`gol-${jugadorId}`);
    if (!element) return;
    
    let count = parseInt(element.textContent) || 0;
    if (count > 0) {
        element.textContent = count - 1;
        actualizarMarcadorPreview();
        
        if (navigator.vibrate) {
            navigator.vibrate(30);
        }
    }
}

function actualizarMarcadorPreview() {
    const golesA = Array.from(document.querySelectorAll('#listaJugadoresA .gol-count'))
        .reduce((sum, el) => sum + (parseInt(el.textContent) || 0), 0);
    
    const golesB = Array.from(document.querySelectorAll('#listaJugadoresB .gol-count'))
        .reduce((sum, el) => sum + (parseInt(el.textContent) || 0), 0);
    
    const previewGolesA = document.getElementById('previewGolesA');
    const previewGolesB = document.getElementById('previewGolesB');
    
    if (previewGolesA) {
        previewGolesA.textContent = golesA;
        if (golesA > 0) animateNumber(previewGolesA);
    }
    
    if (previewGolesB) {
        previewGolesB.textContent = golesB;
        if (golesB > 0) animateNumber(previewGolesB);
    }
}

function animateNumber(element) {
    element.classList.add('number-pop');
    setTimeout(() => {
        element.classList.remove('number-pop');
    }, 300);
}

// ============================================
// FINALIZAR PARTIDO - ENVIAR RESULTADO
// ============================================
async function finalizarPartido() {
    if (!currentFixtureId) {
        showToast('❌ Error: No hay partido seleccionado', 'error');
        return;
    }
    
    // Confirmación
    if (!confirm('¿Estás seguro de finalizar este partido? El resultado quedará registrado permanentemente.')) {
        return;
    }
    
    try {
        showToast('⏳ Procesando resultado...', 'info');
        
        // Recolectar todos los goles
        const goles = [];
        
        // Goles equipo A
        document.querySelectorAll('#listaJugadoresA .jugador-gol-item').forEach(item => {
            const jugadorId = parseInt(item.dataset.jugadorId);
            const golesCount = parseInt(item.querySelector('.gol-count').textContent) || 0;
            
            for (let i = 0; i < golesCount; i++) {
                goles.push({
                    id_jugador: jugadorId,
                    minuto: null // Se puede agregar después
                });
            }
        });
        
        // Goles equipo B
        document.querySelectorAll('#listaJugadoresB .jugador-gol-item').forEach(item => {
            const jugadorId = parseInt(item.dataset.jugadorId);
            const golesCount = parseInt(item.querySelector('.gol-count').textContent) || 0;
            
            for (let i = 0; i < golesCount; i++) {
                goles.push({
                    id_jugador: jugadorId,
                    minuto: null
                });
            }
        });
        
        // Enviar al backend
        const response = await fetch(`${BASE_URL}/api/resultado/ingresar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                id_fixture: currentFixtureId,
                goles: goles
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Resultado registrado exitosamente', 'success');
            closeResultadoModal();
            
            // Recargar página después de 1.5 segundos para actualizar estadísticas
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast('❌ Error: ' + (data.error || 'No se pudo registrar el resultado'), 'error');
        }
    } catch (error) {
        console.error('Error finalizando partido:', error);
        showToast('❌ Error de conexión al guardar resultado', 'error');
    }
}

// ============================================
// TOAST NOTIFICATIONS
// ============================================
function showToast(message, type = 'success') {
    // Remover toasts existentes
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(toast => toast.remove());
    
    // Crear nuevo toast
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'polite');
    
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || 'ℹ️'}</span>
        <span class="toast-message">${escapeHtml(message)}</span>
        <button class="toast-close" onclick="this.parentElement.remove()" aria-label="Cerrar">&times;</button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto-remover después de 3 segundos
    setTimeout(() => {
        toast.classList.add('toast-exit');
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 300);
    }, 3000);
}

// ============================================
// CHECK FLASH MESSAGES (de PHP)
// ============================================
function checkFlashMessages() {
    // Buscar mensaje flash en el DOM (inyectado por PHP)
    const flashMessage = document.querySelector('.flash-message');
    if (flashMessage) {
        const message = flashMessage.dataset.message;
        const type = flashMessage.dataset.type || 'success';
        showToast(message, type);
        
        // Remover del DOM después de mostrar
        setTimeout(() => {
            flashMessage.remove();
        }, 100);
    }
}

// ============================================
// UTILIDADES
// ============================================
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-CL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function formatTime(timeString) {
    if (!timeString) return '-';
    return timeString.substring(0, 5); // HH:MM
}

// ============================================
// EVENT LISTENERS GLOBALES
// ============================================
function initModalEvents() {
    // Cerrar modal al hacer click fuera
    const modal = document.getElementById('resultadoModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeResultadoModal();
            }
        });
    }
    
    // Cerrar modal con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeResultadoModal();
        }
    });
}

// ============================================
// AUTO-REFRESH PARA MARCADOR EN VIVO (opcional)
// ============================================
function iniciarAutoRefresh(intervalo = 30000) {
    setInterval(async () => {
        if (window.location.pathname.includes('/vivo')) {
            await refreshMarcadorVivo();
        }
    }, intervalo);
}

async function refreshMarcadorVivo() {
    try {
        const response = await fetch(`${BASE_URL}/api/marcador/vivo`);
        const data = await response.json();
        
        if (data.success) {
            actualizarMarcadorEnVivo(data.data);
        }
    } catch (error) {
        console.error('Error refrescando marcador:', error);
    }
}

function actualizarMarcadorEnVivo(datos) {
    // Implementar actualización dinámica del marcador en vivo
    // Esto se usará en la página /vivo
    console.log('Actualizando marcador en vivo:', datos);
}

// Función para verificar contraseña de admin
async function verificarPasswordAdmin(password) {
    try {
        const response = await fetch('/api/resultado/verificar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ password: password })
        });

        // Verificar si la respuesta es OK (status 200-299)
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Acceso Autorizado', 'success');
            // Cerrar modal de password y abrir modal de ingreso de resultado
            closePasswordModal(); 
            openResultadoModalInternal(); // Función que muestra el form de resultados
        } else {
            showToast('❌ Contraseña incorrecta', 'error');
        }
    } catch (error) {
        console.error('Error verificando password:', error);
        showToast('❌ Error de conexión', 'error');
    }
}

// ============================================
// EXPORTAR FUNCIONES GLOBALES (para onclick inline)
// ============================================
window.openResultadoModal = openResultadoModal;
window.closeResultadoModal = closeResultadoModal;
window.verificarPassword = verificarPassword;
window.sumarGol = sumarGol;
window.restarGol = restarGol;
window.finalizarPartido = finalizarPartido;
window.showToast = showToast;

console.log('✅ app.js cargado correctamente | Campeonato GOMS 2026');