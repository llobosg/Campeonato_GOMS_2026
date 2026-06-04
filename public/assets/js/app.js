/**
 * app.js - JavaScript Principal Campeonato GOMS 2026
 * Versión Final Limpia y Sin Conflictos
 */

// ============================================
// VARIABLES GLOBALES (DECLARADAS UNA SOLA VEZ)
// ============================================
var currentFixtureId = null;
var currentPartidoData = null;
var vivoIntervalId = null;

// ============================================
// INICIALIZACIÓN GLOBAL
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log("🚀 Iniciando App GOMS 2026...");
    
    // 1. Inicializar Tabs y Fechas
    initTabs(); 
    initTabsWithDefaultDate(2); 
    
    // 2. Inicializar Modales y Eventos
    initModalEvents();
    checkFlashMessages();
    
    // 3. Contador de Visitas
    actualizarContadorVisitas();
    
    console.log("✅ App iniciada correctamente");
});

// ============================================
// NAVEGACIÓN DE FECHAS
// ============================================
function initTabs() {
    const tabs = document.querySelectorAll('.fecha-tab');
    if (tabs.length > 0) {
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.fecha-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.fecha-content').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                const fechaNum = this.dataset.fecha;
                const content = document.getElementById(`fecha-${fechaNum}`);
                if (content) content.classList.add('active');
            });
        });
    }
}

window.seleccionarFecha = function(nroFecha) {
    console.log("Seleccionando fecha:", nroFecha);
    const botones = document.querySelectorAll('.fecha-tab-img');
    botones.forEach(btn => btn.classList.remove('active'));

    const botonActivo = document.querySelector(`.fecha-tab-img[data-fecha="${nroFecha}"]`);
    if (botonActivo) botonActivo.classList.add('active');

    filtrarFixturePorFecha(nroFecha);
}

function filtrarFixturePorFecha(nroFecha) {
    const seccionesPartidos = document.querySelectorAll('.partidos-fecha, .fecha-content');
    seccionesPartidos.forEach(sec => sec.style.display = 'none');

    const seccionSeleccionada = document.getElementById(`fecha-${nroFecha}`);
    if (seccionSeleccionada) {
        seccionSeleccionada.style.display = 'block';
        seccionSeleccionada.style.opacity = 0;
        setTimeout(() => {
            seccionSeleccionada.style.transition = 'opacity 0.3s';
            seccionSeleccionada.style.opacity = 1;
        }, 50);
    }
}

function initTabsWithDefaultDate(defaultFecha = 2) {
    const tabs = document.querySelectorAll('.fecha-tab-img');
    let activeTabFound = false;

    tabs.forEach(tab => tab.classList.remove('active'));
    
    tabs.forEach(tab => {
        const fechaNum = parseInt(tab.getAttribute('data-fecha'));
        if (fechaNum === defaultFecha) {
            tab.classList.add('active');
            activeTabFound = true;
            seleccionarFecha(fechaNum);
        }
    });

    if (!activeTabFound && tabs.length > 0) {
        const firstTab = tabs[0];
        firstTab.classList.add('active');
        const firstFecha = parseInt(firstTab.getAttribute('data-fecha'));
        seleccionarFecha(firstFecha);
    }
}

// ============================================
// MODAL RESULTADOS (ADMIN)
// ============================================

window.openResultadoModal = function(fixtureId) {
    console.log("🔴 DEBUG: Abriendo modal para ID:", fixtureId);
    window.currentFixtureId = fixtureId;

    const modal = document.getElementById('resultadoModal');
    const pasoPassword = document.getElementById('paso-password');
    const pasoGoles = document.getElementById('paso-goles');
    const inputPass = document.getElementById('adminPassword');
    const errorMsg = document.getElementById('passwordError');

    if (!modal) {
        alert("❌ Error crítico: El modal no existe en el HTML.");
        return;
    }

    // Resetear estado
    if (pasoPassword) pasoPassword.style.display = 'block';
    if (pasoGoles) pasoGoles.style.display = 'none';
    if (inputPass) inputPass.value = '';
    if (errorMsg) errorMsg.style.display = 'none';

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    cargarDatosPartido(fixtureId);
};

window.closeResultadoModal = function() {
    const modal = document.getElementById('resultadoModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
};

async function cargarDatosPartido(fixtureId) {
    try {
        const baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : '';
        const url = `${baseUrl}/api/fixture/${fixtureId}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.data) {
            currentPartidoData = data.data;
            actualizarInfoPartido(data.data);
        }
    } catch (error) {
        console.error("Error fetching fixture:", error);
    }
}

function actualizarInfoPartido(partido) {
    const titulo = document.getElementById('modalPartidoTitulo');
    if (titulo) {
        const fechaFormateada = formatDate(partido.fecha);
        const hora = partido.hora ? partido.hora.substring(0, 5) : 'HH:MM';
        titulo.textContent = `Fecha ${partido.nro_fecha} - ${fechaFormateada} ${hora}`;
    }
    
    const previewEquipoA = document.getElementById('previewEquipoA');
    const previewEquipoB = document.getElementById('previewEquipoB');
    if (previewEquipoA) previewEquipoA.textContent = partido.nombre_equipo_a;
    if (previewEquipoB) previewEquipoB.textContent = partido.nombre_equipo_b;
    
    const previewGolesA = document.getElementById('previewGolesA');
    const previewGolesB = document.getElementById('previewGolesB');
    if (previewGolesA) previewGolesA.textContent = '0';
    if (previewGolesB) previewGolesB.textContent = '0';
}

async function verificarPassword() {
    const passwordInput = document.getElementById('adminPassword');
    const errorMsg = document.getElementById('passwordError');
    const password = passwordInput ? passwordInput.value.trim() : '';
    
    if (!password) {
        if (errorMsg) {
            errorMsg.textContent = '⚠️ Ingrese una contraseña';
            errorMsg.style.display = 'block';
        }
        return;
    }
    
    try {
        showToast('⏳ Verificando...', 'info');
        
        const response = await fetch(`${BASE_URL}/api/resultado/verificar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
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

function showGolesStep() {
    const pasoPassword = document.getElementById('paso-password');
    const pasoGoles = document.getElementById('paso-goles');
    if (pasoPassword) pasoPassword.style.display = 'none';
    if (pasoGoles) pasoGoles.style.display = 'block';
}

async function cargarJugadoresPartido(fixtureId) {
    if (!fixtureId || !currentPartidoData) return;
    
    try {
        showToast('⏳ Cargando jugadores...', 'info');
        await Promise.all([
            cargarListaJugadores(currentPartidoData.equipo_a, 'A'),
            cargarListaJugadores(currentPartidoData.equipo_b, 'B')
        ]);
        showToast('✅ Jugadores cargados', 'success');
    } catch (error) {
        console.error('Error cargando jugadores:', error);
    }
}

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
                        <button type="button" class="btn-minus" onclick="restarGol(${jugador.id_jugador}, '${lado}')">−</button>
                        <span class="gol-count" id="gol-${jugador.id_jugador}">0</span>
                        <button type="button" class="btn-plus" onclick="sumarGol(${jugador.id_jugador}, '${lado}')">+</button>
                    </div>
                `;
                container.appendChild(div);
            });
        } else {
            container.innerHTML = '<p class="no-jugadores">Sin jugadores registrados</p>';
        }
    } catch (error) {
        console.error(`Error cargando jugadores equipo ${lado}:`, error);
        container.innerHTML = '<p class="error-message">Error al cargar</p>';
    }
}

window.sumarGol = function(jugadorId, lado) {
    const element = document.getElementById(`gol-${jugadorId}`);
    if (!element) return;
    let count = parseInt(element.textContent) || 0;
    element.textContent = count + 1;
    actualizarMarcadorPreview();
}

window.restarGol = function(jugadorId, lado) {
    const element = document.getElementById(`gol-${jugadorId}`);
    if (!element) return;
    let count = parseInt(element.textContent) || 0;
    if (count > 0) {
        element.textContent = count - 1;
        actualizarMarcadorPreview();
    }
}

function actualizarMarcadorPreview() {
    const golesA = Array.from(document.querySelectorAll('#listaJugadoresA .gol-count'))
        .reduce((sum, el) => sum + (parseInt(el.textContent) || 0), 0);
    
    const golesB = Array.from(document.querySelectorAll('#listaJugadoresB .gol-count'))
        .reduce((sum, el) => sum + (parseInt(el.textContent) || 0), 0);
    
    const previewGolesA = document.getElementById('previewGolesA');
    const previewGolesB = document.getElementById('previewGolesB');
    
    if (previewGolesA) previewGolesA.textContent = golesA;
    if (previewGolesB) previewGolesB.textContent = golesB;
}

window.finalizarPartido = async function() {
    if (!currentFixtureId) {
        showToast('❌ Error: No hay partido seleccionado', 'error');
        return;
    }
    
    if (!confirm('¿Estás seguro de finalizar este partido?')) return;
    
    try {
        showToast('⏳ Procesando resultado...', 'info');
        
        const goles = [];
        document.querySelectorAll('#listaJugadoresA .jugador-gol-item').forEach(item => {
            const jugadorId = parseInt(item.dataset.jugadorId);
            const golesCount = parseInt(item.querySelector('.gol-count').textContent) || 0;
            for (let i = 0; i < golesCount; i++) {
                goles.push({ id_jugador: jugadorId, minuto: null });
            }
        });
        
        document.querySelectorAll('#listaJugadoresB .jugador-gol-item').forEach(item => {
            const jugadorId = parseInt(item.dataset.jugadorId);
            const golesCount = parseInt(item.querySelector('.gol-count').textContent) || 0;
            for (let i = 0; i < golesCount; i++) {
                goles.push({ id_jugador: jugadorId, minuto: null });
            }
        });
        
        const response = await fetch(`${BASE_URL}/api/resultado/ingresar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_fixture: currentFixtureId, goles: goles })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Resultado registrado', 'success');
            closeResultadoModal();
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast('❌ Error: ' + (data.error || 'No se pudo registrar'), 'error');
        }
    } catch (error) {
        console.error('Error finalizando partido:', error);
        showToast('❌ Error de conexión', 'error');
    }
}

// ============================================
// MODAL RESULTADOS EN VIVO (AUTO-REFRESH)
// ============================================

window.openModalVivo = function() {
    const modal = document.getElementById('modalVivo');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        cargarDatosVivo(); 
        
        if (!vivoIntervalId) {
            vivoIntervalId = setInterval(cargarDatosVivo, 5000);
            console.log("🔴 Auto-refresh iniciado para Modal Vivo");
        }
    }
};

window.closeModalVivo = function() {
    const modal = document.getElementById('modalVivo');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        if (vivoIntervalId) {
            clearInterval(vivoIntervalId);
            vivoIntervalId = null;
            console.log("⚪ Auto-refresh detenido");
        }
    }
};

async function cargarDatosVivo() {
    try {
        let fixtureId = window.currentFixtureId || 1; 
        const response = await fetch(`${BASE_URL}/api/fixture/${fixtureId}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            const partido = data.data;
            actualizarMarcadorVivo('vivo-score-a', partido.goles_a || 0);
            actualizarMarcadorVivo('vivo-score-b', partido.goles_b || 0);
            
            document.getElementById('vivo-team-a-name').textContent = partido.nombre_equipo_a;
            document.getElementById('vivo-team-b-name').textContent = partido.nombre_equipo_b;
            
            actualizarGoleadoresVivo(partido.id_fixture);
        }
    } catch (error) {
        console.error('Error refrescando vivo:', error);
    }
}

function actualizarMarcadorVivo(elementId, nuevoValor) {
    const element = document.getElementById(elementId);
    if (element && parseInt(element.textContent) !== nuevoValor) {
        element.style.transform = "scale(1.5)";
        element.style.color = "#fff";
        element.textContent = nuevoValor;
        setTimeout(() => {
            element.style.transform = "scale(1)";
            element.style.color = "var(--color-primary)";
        }, 300);
    }
}

async function actualizarGoleadoresVivo(fixtureId) {
    try {
        const response = await fetch(`${BASE_URL}/api/goles?fixture=${fixtureId}`);
        const data = await response.json();
        const scorersUl = document.getElementById('vivo-scorers-ul');
        
        if (data.success && data.data && data.data.length > 0) {
            let html = '';
            data.data.forEach(gol => {
                html += `<li> ${gol.nombre_jugador} <small>(${gol.minuto ? gol.minuto + "'" : ''})</small></li>`;
            });
            scorersUl.innerHTML = html;
        } else {
            scorersUl.innerHTML = '<li>Sin goles registrados aún</li>';
        }
    } catch (error) {
        console.error('Error cargando goleadores vivo:', error);
    }
}

// ============================================
// UTILIDADES Y TOASTS
// ============================================
window.showToast = function(message, type = 'success') {
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span>${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
        <span>${escapeHtml(message)}</span>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;color:white;cursor:pointer;">&times;</button>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
}

function formatDate(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString + 'T00:00:00');
        return date.toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch (e) {
        return dateString;
    }
}

function initModalEvents() {
    // Listeners globales ya manejados en DOMContentLoaded
}

function checkFlashMessages() {
    const flashMessage = document.querySelector('.flash-message');
    if (flashMessage) {
        showToast(flashMessage.dataset.message, flashMessage.dataset.type || 'success');
        setTimeout(() => flashMessage.remove(), 100);
    }
}

// ============================================
// ANALYTICS Y COMPARTIR
// ============================================
async function actualizarContadorVisitas() {
    const element = document.getElementById('visit-count');
    if (!element) return;
    try {
        const response = await fetch('/api/visitas.php');
        const data = await response.json();
        if (data.count !== undefined) {
            element.textContent = data.count.toLocaleString('es-CL');
        }
    } catch (error) {
        element.textContent = '--';
    }
}

window.compartirMarcadorWSP = function() {
    const equipoA = document.getElementById('vivo-team-a-name')?.textContent || 'Equipo A';
    const equipoB = document.getElementById('vivo-team-b-name')?.textContent || 'Equipo B';
    const scoreA = document.getElementById('vivo-score-a')?.textContent || '0';
    const scoreB = document.getElementById('vivo-score-b')?.textContent || '0';
    const fecha = document.getElementById('vivo-match-date')?.textContent || '';
    
    let mensaje = `⚽ *CAMPEONATO GOMS 2026 - EN VIVO* ⚽\n\n`;
    mensaje += `🏆 ${fecha}\n━━━━━━━━━━━━━━━\n`;
    mensaje += `*${equipoA.toUpperCase()}* ${scoreA} - ${scoreB} *${equipoB.toUpperCase()}*\n`;
    mensaje += `━━━━━━━━━━━━━━━\n\n`;
    
    const scorersList = document.getElementById('vivo-scorers-ul');
    if (scorersList && scorersList.children.length > 0 && scorersList.children[0].textContent !== 'Cargando...') {
        mensaje += `⚡ *Goleadores del Partido:*\n`;
        Array.from(scorersList.children).forEach(li => {
            mensaje += `• ${li.textContent.trim()}\n`;
        });
        mensaje += `\n`;
    }
    
    mensaje += `🔗 Sigue el resultado:\nhttps://campeonatogoms2026.up.railway.app\n\n_Enviado desde CanchaSport_ 📱`;
    
    const urlWhatsApp = `https://wa.me/?text=${encodeURIComponent(mensaje)}`;
    window.open(urlWhatsApp, '_blank');
    
    if (typeof gtag !== 'undefined') {
        gtag('event', 'compartir_whatsapp', { 'match': `${equipoA} vs ${equipoB}` });
    }
}

console.log('✅ app.js cargado correctamente | Campeonato GOMS 2026');