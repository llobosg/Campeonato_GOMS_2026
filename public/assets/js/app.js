/**
 * app.js - JavaScript Principal Campeonato GOMS 2026
 */

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    initTabs(); // Inicializa los tabs originales si existen
    initModalEvents();
    checkFlashMessages();
});

// ============================================
// TABS DE FECHAS ORIGINALES (Si usas texto)
// ============================================
function initTabs() {
    const tabs = document.querySelectorAll('.fecha-tab'); // Clase original
    if (tabs.length > 0) {
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.fecha-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.fecha-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                const fechaNum = this.dataset.fecha;
                const content = document.getElementById(`fecha-${fechaNum}`);
                if (content) {
                    content.classList.add('active');
                }
            });
        });
    }
}

// ============================================
// NUEVA FUNCIÓN PARA IMÁGENES DE FECHAS
// ============================================
function seleccionarFecha(nroFecha) {
    console.log("Seleccionando fecha:", nroFecha);
    
    // 1. Quitar clase 'active' de todos los botones de imagen
    const botones = document.querySelectorAll('.fecha-tab-img');
    botones.forEach(btn => btn.classList.remove('active'));

    // 2. Agregar clase 'active' al botón clickeado
    const botonActivo = document.querySelector(`.fecha-tab-img[data-fecha="${nroFecha}"]`);
    if (botonActivo) {
        botonActivo.classList.add('active');
    }

    // 3. Filtrar el fixture visualmente
    filtrarFixturePorFecha(nroFecha);
}

function filtrarFixturePorFecha(nroFecha) {
    // Ocultar todas las secciones de partidos
    const seccionesPartidos = document.querySelectorAll('.partidos-fecha, .fecha-content');
    seccionesPartidos.forEach(sec => sec.style.display = 'none');

    // Mostrar solo la sección correspondiente a la fecha seleccionada
    // Nota: Asegúrate que tus divs de contenido tengan id="fecha-1", id="fecha-2", etc.
    const seccionSeleccionada = document.getElementById(`fecha-${nroFecha}`);
    if (seccionSeleccionada) {
        seccionSeleccionada.style.display = 'block';
        // Pequeña animación opcional
        seccionSeleccionada.style.opacity = 0;
        setTimeout(() => {
            seccionSeleccionada.style.transition = 'opacity 0.3s';
            seccionSeleccionada.style.opacity = 1;
        }, 50);
    } else {
        console.warn("No se encontró el elemento con ID: fecha-" + nroFecha);
    }
}

// ============================================
// MODAL DE RESULTADO Y CONTRASEÑA
// ============================================
function openResultadoModal(fixtureId) {
    currentFixtureId = fixtureId;
    const modal = document.getElementById('resultadoModal');
    
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        showPasswordStep();
        
        // Animación entrada
        const content = modal.querySelector('.modal-content');
        if(content) {
            content.style.animation = 'none';
            setTimeout(() => content.style.animation = 'slideUp 0.3s ease', 10);
        }
    }
}

function closeResultadoModal() {
    const modal = document.getElementById('resultadoModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        currentFixtureId = null;
        currentPartidoData = null;
    }
}

function showPasswordStep() {
    const pasoPassword = document.getElementById('paso-password');
    const pasoGoles = document.getElementById('paso-goles');
    
    if (pasoPassword) pasoPassword.style.display = 'block';
    if (pasoGoles) pasoGoles.style.display = 'none';
    
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
        
        // Usamos BASE_URL definida en header.php
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
        showToast('⏳ Cargando datos...', 'info');
        
        const response = await fetch(`${BASE_URL}/api/fixture/${fixtureId}`);
        const data = await response.json();
        
        if (data.success) {
            currentPartidoData = data.data;
            actualizarInfoPartido(currentPartidoData);
            
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
        showToast('❌ Error de conexión', 'error');
    }
}

function actualizarInfoPartido(partido) {
    // Título del modal
    const titulo = document.getElementById('modalPartidoTitulo');
    if (titulo) {
        // Usamos la fecha del objeto partido que viene de la BD
        // Si quieres forzar la fecha de HOY, descomenta la siguiente línea:
        // const fechaUsar = new Date().toISOString().split('T')[0]; 
        
        const fechaUsar = partido.fecha; // Usa la fecha real del fixture
        
        const fechaFormateada = formatDate(fechaUsar);
        const hora = partido.hora ? partido.hora.substring(0, 5) : 'HH:MM';
        
        // Formato: "Fecha X - DD/MM/YYYY HH:MM"
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

function sumarGol(jugadorId, lado) {
    const element = document.getElementById(`gol-${jugadorId}`);
    if (!element) return;
    let count = parseInt(element.textContent) || 0;
    element.textContent = count + 1;
    actualizarMarcadorPreview();
}

function restarGol(jugadorId, lado) {
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

async function finalizarPartido() {
    if (!currentFixtureId) {
        showToast(' Error: No hay partido seleccionado', 'error');
        return;
    }
    
    if (!confirm('¿Estás seguro de finalizar este partido?')) return;
    
    try {
        showToast('⏳ Procesando...', 'info');
        
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
// UTILIDADES Y TOASTS
// ============================================
function showToast(message, type = 'success') {
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
        // Asumiendo formato YYYY-MM-DD
        const date = new Date(dateString + 'T00:00:00'); // Forzar zona horaria local si es necesario
        return date.toLocaleDateString('es-CL', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    } catch (e) {
        console.error("Error formateando fecha:", e);
        return dateString; // Devolver original si falla
    }
}

function initModalEvents() {
    const modal = document.getElementById('resultadoModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeResultadoModal();
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeResultadoModal();
    });
}

function checkFlashMessages() {
    const flashMessage = document.querySelector('.flash-message');
    if (flashMessage) {
        showToast(flashMessage.dataset.message, flashMessage.dataset.type || 'success');
        setTimeout(() => flashMessage.remove(), 100);
    }
}

// ============================================
// LÓGICA DE VISIBILIDAD BOTÓN RESULTADO
// ============================================

function verificarVisibilidadBotonesResultado() {
    const ahora = new Date();
    const fechaHoy = ahora.toISOString().split('T')[0]; // YYYY-MM-DD
    
    // Seleccionar todas las tarjetas de partidos que tengan botón de resultado
    const matchCards = document.querySelectorAll('.match-card');
    
    matchCards.forEach(card => {
        const btn = card.querySelector('.btn-resultado');
        if (!btn) return;

        // Obtener fecha y hora del partido desde atributos data-
        // Asegúrate que tu HTML tenga: <div class="match-card" data-fecha="2026-06-03" data-hora="19:00">
        const fechaPartido = card.getAttribute('data-fecha');
        const horaPartidoStr = card.getAttribute('data-hora');
        
        if (!fechaPartido || !horaPartidoStr) return;

        // Crear objeto Date para el inicio del partido
        const [horas, minutos] = horaPartidoStr.split(':').map(Number);
        const inicioPartido = new Date(fechaPartido);
        inicioPartido.setHours(horas, minutos, 0, 0);

        // Calcular límite: 5 minutos antes del inicio
        const limiteInicio = new Date(inicioPartido.getTime() - 5 * 60 * 1000);
        
        // Calcular límite fin: 2 horas después del inicio (para evitar que se cierre inmediatamente)
        const limiteFin = new Date(inicioPartido.getTime() + 120 * 60 * 1000);

        // Lógica: Mostrar si "Ahora" está entre [Inicio - 5min] y [Inicio + 2h]
        // Y además, que sea el día correcto (aunque la comparación de fechas completa ya lo cubre)
        if (ahora >= limiteInicio && ahora <= limiteFin) {
            btn.classList.add('btn-visible');
            btn.disabled = false;
        } else {
            btn.classList.remove('btn-visible');
            btn.disabled = true; // Opcional: deshabilitar click
        }
    });
}

// Llamar a la función al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // ... tus otras inicializaciones ...
    verificarVisibilidadBotonesResultado();
    
    // Opcional: Revisar cada minuto por si cambia la hora mientras el usuario está en la página
    setInterval(verificarVisibilidadBotonesResultado, 60000);
});

// ============================================
// LÓGICA MODAL RESULTADOS EN VIVO (AUTO-REFRESH)
// ============================================

let vivoIntervalId = null; // Variable para guardar el ID del intervalo

function openModalVivo() {
    const modal = document.getElementById('modalVivo');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Cargar datos inmediatamente
        cargarDatosVivo(); 
        
        // Iniciar auto-refresh cada 5 segundos (5000 ms)
        if (!vivoIntervalId) {
            vivoIntervalId = setInterval(cargarDatosVivo, 5000);
            console.log("🔴 Auto-refresh iniciado para Modal Vivo");
        }
    }
}

function closeModalVivo() {
    const modal = document.getElementById('modalVivo');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        
        // Detener el auto-refresh para ahorrar recursos
        if (vivoIntervalId) {
            clearInterval(vivoIntervalId);
            vivoIntervalId = null;
            console.log("⚪ Auto-refresh detenido");
        }
    }
}

async function cargarDatosVivo() {
    try {
        // Usamos el currentFixtureId si existe, o buscamos el partido "activo"
        // Para este ejemplo, asumimos que currentFixtureId se setea al abrir el modal de resultados
        // O podrías pasar un ID específico de un partido "estrella"
        
        let fixtureId = currentFixtureId || 1; // Fallback
        
        // Fetch a la API de fixture para obtener marcadores y goles
        const response = await fetch(`${BASE_URL}/api/fixture/${fixtureId}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            const partido = data.data;
            
            // 1. Actualizar Marcadores con animación si cambian
            actualizarMarcadorVivo('vivo-score-a', partido.goles_equipo_a || 0);
            actualizarMarcadorVivo('vivo-score-b', partido.goles_equipo_b || 0);
            
            // 2. Actualizar Nombres de Equipos (por si acaso)
            document.getElementById('vivo-team-a-name').textContent = partido.nombre_equipo_a;
            document.getElementById('vivo-team-b-name').textContent = partido.nombre_equipo_b;
            
            // 3. Actualizar Lista de Goleadores
            actualizarGoleadoresVivo(partido.id_fixture);
        }
    } catch (error) {
        console.error('Error refrescando vivo:', error);
    }
}

// Función auxiliar para actualizar números con animación suave
function actualizarMarcadorVivo(elementId, nuevoValor) {
    const element = document.getElementById(elementId);
    if (element && parseInt(element.textContent) !== nuevoValor) {
        // Animación simple de "pop"
        element.style.transform = "scale(1.5)";
        element.style.color = "#fff";
        element.textContent = nuevoValor;
        
        setTimeout(() => {
            element.style.transform = "scale(1)";
            element.style.color = "var(--color-primary)";
        }, 300);
    }
}

// Función para cargar y mostrar goleadores del partido
async function actualizarGoleadoresVivo(fixtureId) {
    try {
        // Necesitas un endpoint que devuelva los goles de un partido específico
        // Ejemplo: /api/goles/partido/{id}
        // Si no tienes ese endpoint, puedes filtrar desde el array de jugadores si lo traes todo
        
        // Por ahora, simularemos que traemos los goles desde la misma respuesta del fixture 
        // o haremos un fetch separado si tienes la tabla 'goles' accesible via API.
        
        // Supongamos que tienes una API /api/goles?fixture=ID
        const response = await fetch(`${BASE_URL}/api/goles?fixture=${fixtureId}`);
        const data = await response.json();
        
        const scorersUl = document.getElementById('vivo-scorers-ul');
        
        if (data.success && data.data && data.data.length > 0) {
            let html = '';
            data.data.forEach(gol => {
                // Asumiendo que gol tiene: nombre_jugador, minuto, equipo
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
// EVENTOS GOOGLE ANALYTICS - DATOS POTENCIADORES
// ============================================

// Rastrear cuando ven posiciones
function trackVerPosiciones(grupo) {
    if (typeof gtag !== 'undefined') {
        gtag('event', 'ver_posiciones', {
            'grupo': grupo,
            'engagement_level': 'high'
        });
    }
}

// Rastrear cuando ingresan resultado (Admin)
function trackIngresarResultado(fixtureId) {
    if (typeof gtag !== 'undefined') {
        gtag('event', 'ingresar_resultado', {
            'fixture_id': fixtureId,
            'user_role': 'admin'
        });
    }
}

// Rastrear apertura del modal En Vivo
function trackModalVivo() {
    if (typeof gtag !== 'undefined') {
        gtag('event', 'modal_vivo_abierto', {
            'engagement_level': 'very_high'
        });
    }
}

// ============================================
// CONTADOR DE VISITAS REALES (VÍA GOOGLE ANALYTICS)
// ============================================
async function actualizarContadorVisitas() {
    const element = document.getElementById('visit-count');
    if (!element) return;

    try {
        // Llamamos a nuestro propio backend que consulta GA4
        const response = await fetch('/api/visitas.php');
        const data = await response.json();
        
        if (data.count !== undefined) {
            element.textContent = data.count.toLocaleString('es-CL');
        }
    } catch (error) {
        console.error('Error cargando contador:', error);
        element.textContent = '--';
    }
}

// ============================================
// COMPARTIR MARCADOR POR WHATSAPP
// ============================================
function compartirMarcadorWSP() {
    // Obtener datos actuales del DOM del modal vivo
    const equipoA = document.getElementById('vivo-team-a-name')?.textContent || 'Equipo A';
    const equipoB = document.getElementById('vivo-team-b-name')?.textContent || 'Equipo B';
    const scoreA = document.getElementById('vivo-score-a')?.textContent || '0';
    const scoreB = document.getElementById('vivo-score-b')?.textContent || '0';
    const fecha = document.getElementById('vivo-match-date')?.textContent || '';
    
    // Construir mensaje con formato WhatsApp (*negrita*, _cursiva_, ~tachado~)
    let mensaje = `⚽ *CAMPEONATO GOMS 2026 - EN VIVO* ⚽\n\n`;
    mensaje += `🏆 ${fecha}\n`;
    mensaje += `━━━━━━━━━━━━━━━\n`;
    mensaje += `*${equipoA.toUpperCase()}* ${scoreA} - ${scoreB} *${equipoB.toUpperCase()}*\n`;
    mensaje += `━━━━━━━━━━━━━━━\n\n`;
    
    // Agregar goleadores si existen
    const scorersList = document.getElementById('vivo-scorers-ul');
    if (scorersList && scorersList.children.length > 0 && scorersList.children[0].textContent !== 'Cargando...') {
        mensaje += `⚡ *Goleadores del Partido:*\n`;
        Array.from(scorersList.children).forEach(li => {
            mensaje += `• ${li.textContent.trim()}\n`;
        });
        mensaje += `\n`;
    }
    
    mensaje += ` Sigue el resultado en tiempo real:\n`;
    mensaje += `https://campeonatogoms2026.up.railway.app\n\n`;
    mensaje += `_Enviado desde CanchaSport_ 📱`;
    
    // Codificar para URL
    const textoCodificado = encodeURIComponent(mensaje);
    const urlWhatsApp = `https://wa.me/?text=${textoCodificado}`;
    
    // Abrir WhatsApp (Web o App según dispositivo)
    window.open(urlWhatsApp, '_blank');
    
    // Opcional: Rastrear evento en GA4
    if (typeof gtag !== 'undefined') {
        gtag('event', 'compartir_whatsapp', {
            'match': `${equipoA} vs ${equipoB}`,
            'score': `${scoreA}-${scoreB}`
        });
    }
}

// Llamar cuando cargue la página
document.addEventListener('DOMContentLoaded', () => {
    actualizarContadorVisitas();
});

// Exportar funciones globales
window.openModalVivo = openModalVivo;
window.closeModalVivo = closeModalVivo;

// ============================================
// EXPORTAR FUNCIONES AL WINDOW (CRUCIAL PARA ONCLICK)
// ============================================
window.seleccionarFecha = seleccionarFecha;
window.filtrarFixturePorFecha = filtrarFixturePorFecha;
window.openResultadoModal = openResultadoModal;
window.closeResultadoModal = closeResultadoModal;
window.verificarPassword = verificarPassword;
window.sumarGol = sumarGol;
window.restarGol = restarGol;
window.finalizarPartido = finalizarPartido;
window.showToast = showToast;

console.log('✅ app.js cargado correctamente | Campeonato GOMS 2026');