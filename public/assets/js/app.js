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
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric' });
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
// LÓGICA MODAL RESULTADOS EN VIVO
// ============================================

function openModalVivo() {
    const modal = document.getElementById('modalVivo');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Evitar scroll
        
        // Cargar datos del partido actual
        // NOTA: Aquí asumimos que quieres ver el primer partido de la fecha activa
        // O podrías pasar un ID específico si lo tienes.
        cargarDatosVivo(); 
    }
}

function closeModalVivo() {
    const modal = document.getElementById('modalVivo');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

async function cargarDatosVivo() {
    // Ejemplo: Obtener el fixture de la fecha activa o un ID hardcodeado para prueba
    // Para producción, deberías tener una API que devuelva "el partido que se está jugando ahora"
    
    // Simulación: Usamos el currentFixtureId si existe, o buscamos uno activo
    // Si no hay lógica de "partido actual", usaremos el primero de la lista como ejemplo
    
    try {
        showToast(' Cargando transmisión...', 'info');
        
        // Aquí debes llamar a tu API. Ejemplo: /api/fixture/vivo o similar
        // Como no tenemos ese endpoint específico, usaremos el currentFixtureId si está definido
        // O podrías hacer fetch a /api/fixture/1 (ejemplo)
        
        let fixtureId = currentFixtureId || 1; // Fallback a ID 1 para prueba
        
        const response = await fetch(`${BASE_URL}/api/fixture/${fixtureId}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            const partido = data.data;
            
            // Actualizar UI del Modal Vivo
            document.getElementById('vivo-team-a-name').textContent = partido.nombre_equipo_a;
            document.getElementById('vivo-team-b-name').textContent = partido.nombre_equipo_b;
            
            // Marcadores (Si ya hay resultado guardado)
            // Nota: Si el partido está en vivo y aún no se guarda el resultado final, 
            // deberías tener una columna de 'marcador_parcial' en tu BD o calcularlo desde los goles.
            // Por ahora mostramos 0-0 o el resultado final si existe.
            document.getElementById('vivo-score-a').textContent = partido.goles_equipo_a || 0;
            document.getElementById('vivo-score-b').textContent = partido.goles_equipo_b || 0;
            
            document.getElementById('vivo-match-time').textContent = partido.hora ? partido.hora.substring(0,5) : '--:--';
            document.getElementById('vivo-match-date').textContent = `Fecha ${partido.nro_fecha}`;
            
            // Cargar goleadores del partido (si existen)
            const scorersUl = document.getElementById('vivo-scorers-ul');
            scorersUl.innerHTML = '<li>Cargando goleadores...</li>';
            
            // Fetch de goleadores específicos de este partido (necesitarás un endpoint o filtrar)
            // Por ahora, dejaremos un placeholder o cargaremos todos los jugadores si no hay endpoint específico
            scorersUl.innerHTML = '<li>Detalle de goles disponible al finalizar</li>';
            
        } else {
            showToast('❌ No se encontraron datos del partido', 'error');
        }
    } catch (error) {
        console.error('Error cargando vivo:', error);
        showToast('❌ Error de conexión', 'error');
    }
}

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