let mesasSeleccionadas = [];
let reservas = [];
let todasLasReservas = [];

// Inicializar la página
document.addEventListener('DOMContentLoaded', function() {
    inicializarFechas();
    cargarMesas();
    cargarReservas();
    configurarEventos();
});

// Configurar eventos
function configurarEventos() {
    document.getElementById('fechaReserva').addEventListener('change', cargarMesas);
    document.getElementById('horaReserva').addEventListener('change', cargarMesas);
    
    // Filtros
    document.getElementById('filtroEstado').addEventListener('change', cargarReservas);
    document.getElementById('filtroFecha').addEventListener('change', cargarReservas);
    
    // Filtro de búsqueda
    document.getElementById('filtroBusqueda').addEventListener('input', cargarReservas);
    document.getElementById('btnGuardarReserva').addEventListener('click', guardarReserva);
    document.querySelector('.close').addEventListener('click', cerrarModal);
}

// Inicializar fechas
function inicializarFechas() {
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('fechaReserva').min = hoy;
    
    // QUITAR FILTRO DE FECHA POR DEFECTO
    document.getElementById('filtroFecha').value = '';
    const ahora = new Date();
    const horaProxima = new Date(ahora.getTime() + 60 * 60 * 1000);
    const horaFormateada = horaProxima.getHours().toString().padStart(2, '0') + ':00';
    document.getElementById('horaReserva').value = horaFormateada;
}

function cargarMesas() {
    fetch("reservas.php?action=cargar_mesas")
        .then(res => res.json())
        .then(mesas => {
            const contenedor = document.getElementById('contenedor-mesas');
            contenedor.innerHTML = '';
            
            mesas.forEach(mesa => {
                const btnMesa = document.createElement("button");
                btnMesa.type = "button";
                btnMesa.className = `mesa-btn`;
                
                if (mesa.estado === 'ocupada') {
                    btnMesa.classList.add('ocupada');
                } else if (mesa.estado === 'reservada') {
                    btnMesa.classList.add('reservada');
                }
                
                btnMesa.innerHTML = `Mesa ${mesa.numero_mesa}<span class="info-mesa">P${mesa.piso}${mesa.ubicacion_especial === 'balcon' ? ' balcon ' : ''}</span>`;
                btnMesa.dataset.mesaId = mesa.id;
                btnMesa.dataset.mesaNumero = mesa.numero_mesa;
                
                if (mesa.estado === 'libre') {
                    btnMesa.onclick = function() {
                        toggleMesa(mesa.id, mesa.numero_mesa);
                    };
                } else {
                    btnMesa.title = `Mesa ${mesa.estado}`;
                }
                
                contenedor.appendChild(btnMesa);
            });
            
            actualizarBotonesMesas();
        })
        .catch(error => console.error('Error cargando mesas:', error));
}

// Seleccionar/Des seleccionar mesa
function toggleMesa(mesaId, numeroMesa) {
    const index = mesasSeleccionadas.findIndex(m => m.id === mesaId);
    
    if (index === -1) {
        if (mesasSeleccionadas.length >= 3) {
            mostrarMensajeMesas("⚠️ Máximo 3 mesas permitidas");
            return;
        }
        mesasSeleccionadas.push({ id: mesaId, numero: numeroMesa });
    } else {
        mesasSeleccionadas.splice(index, 1);
    }
    
    actualizarBotonesMesas();
    document.getElementById('mensajeMesas').style.display = 'none';
}

function actualizarBotonesMesas() {
    document.querySelectorAll('.mesa-btn').forEach(btn => {
        btn.classList.remove('seleccionada');
    });
    
    mesasSeleccionadas.forEach(mesa => {
        const btn = document.querySelector(`.mesa-btn[data-mesa-id="${mesa.id}"]`);
        if (btn) {
            btn.classList.add('seleccionada');
        }
    });
}

function validarFormulario() {
    const camposRequeridos = [
        { id: 'nombreCliente', nombre: 'Nombres' },
        { id: 'apellidoCliente', nombre: 'Apellidos' },
        { id: 'fechaReserva', nombre: 'Fecha de Reserva' },
        { id: 'horaReserva', nombre: 'Hora de Reserva' },
        { id: 'cantidadPersonas', nombre: 'Cantidad de Personas' }
    ];
    
    for (let campo of camposRequeridos) {
        const input = document.getElementById(campo.id);
        if (!input.value.trim()) {
            alert(`⚠️ Completa el campo: ${campo.nombre}`);
            input.focus();
            return false;
        }
    }
    
    if (mesasSeleccionadas.length === 0) {
        mostrarMensajeMesas("⚠️ Selecciona al menos 1 mesa");
        return false;
    }
    
    return true;
}

// Guardar reserva
function guardarReserva() {
    if (!validarFormulario()) return;
    
    const datos = {
        nombres: document.getElementById('nombreCliente').value.trim(),
        apellidos: document.getElementById('apellidoCliente').value.trim(),
        telefono: document.getElementById('telefonoCliente').value.trim(),
        fecha_reserva: document.getElementById('fechaReserva').value,
        hora_reserva: document.getElementById('horaReserva').value,
        cantidad_personas: parseInt(document.getElementById('cantidadPersonas').value),
        cantidad_mesas: mesasSeleccionadas.length,
        mesas_seleccionadas: mesasSeleccionadas.map(m => m.numero).join(','),
        observaciones: document.getElementById('observaciones').value.trim()
    };
    
    fetch('reservas.php?action=guardar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
    .then(res => res.json())
    .then(resultado => {
        if (resultado.success) {
            alert('✅ Reserva guardada correctamente');
            limpiarFormulario();
            cargarReservas();
            cargarMesas();
        } else {
            alert('❌ Error al guardar reserva: ' + (resultado.error || ''));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error de conexión');
    });
}

// Cargar y mostrar reservas
function cargarReservas() {
    const estado = document.getElementById('filtroEstado').value;
    const fecha = document.getElementById('filtroFecha').value;
    const busqueda = document.getElementById('filtroBusqueda').value.trim();
    
    fetch(`reservas.php?action=listar&estado=${estado}&fecha=${fecha}&busqueda=${busqueda}`)
        .then(res => res.json())
        .then(data => {
            reservas = data;
            mostrarReservas();
        })
        .catch(error => {
            console.error('Error cargando reservas:', error);
        });
}

// Mostrar reservas
function mostrarReservas() {
    const contenedor = document.getElementById('listaReservas');
    
    if (reservas.length === 0) {
        contenedor.innerHTML = '<p class="sin-reservas">No hay reservas para mostrar</p>';
        return;
    }
    
    contenedor.innerHTML = reservas.map(reserva => `
        <div class="reserva-item ${reserva.estado}">
            <div class="reserva-info">
                <h4>${reserva.nombres} ${reserva.apellidos}</h4>
                <p>Fecha: ${new Date(reserva.fecha_reserva).toLocaleDateString()} - Hora: ${reserva.hora_reserva.substr(0,5)}</p>
                <p>Personas: ${reserva.cantidad_personas} - Mesas: ${reserva.cantidad_mesas}</p>
                <p>Mesas: ${reserva.mesas_seleccionadas}</p>
                <p>Teléfono: ${reserva.telefono || 'No especificado'}</p>
                <p>Estado: <span class="estado-badge estado-${reserva.estado}">${reserva.estado}</span></p>
            </div>
            <div class="reserva-acciones">
                <button class="btn-accion btn-detalles" onclick="verDetalles(${reserva.id})">Detalles</button>
                ${reserva.estado === 'pendiente' ? `
                    <button class="btn-accion btn-confirmar" onclick="actualizarEstado(${reserva.id}, 'confirmada')">Confirmar</button>
                    <button class="btn-accion btn-cancelar" onclick="actualizarEstado(${reserva.id}, 'cancelada')">Cancelar</button>
                ` : ''}
                ${reserva.estado === 'confirmada' ? `
                    <button class="btn-accion btn-confirmar" onclick="actualizarEstado(${reserva.id}, 'cumplida')">Cumplida</button>
                ` : ''}
            </div>
        </div>
    `).join('');
}

// Funciones auxiliares
function mostrarMensajeMesas(mensaje) {
    const elemento = document.getElementById('mensajeMesas');
    elemento.textContent = mensaje;
    elemento.style.display = 'block';
}

function limpiarFormulario() {
    document.getElementById('nombreCliente').value = '';
    document.getElementById('apellidoCliente').value = '';
    document.getElementById('telefonoCliente').value = '';
    document.getElementById('cantidadPersonas').value = '';
    document.getElementById('observaciones').value = '';
    mesasSeleccionadas = [];
    actualizarBotonesMesas();
}

function actualizarEstado(reservaId, nuevoEstado) {
    if (confirm(`¿Cambiar estado a "${nuevoEstado}"?`)) {
        fetch('reservas.php?action=actualizar_estado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: reservaId, estado: nuevoEstado })
        })
        .then(res => res.json())
        .then(resultado => {
            if (resultado.success) {
                alert('✅ Estado actualizado');
                cargarReservas();
                cargarMesas();
            }
        });
    }
}

function verDetalles(reservaId) {
    fetch(`reservas.php?action=detalles&id=${reservaId}`)
        .then(res => res.json())
        .then(reserva => {
            document.getElementById('detallesReserva').innerHTML = `
                <div class="detalle-item">
                    <strong>Cliente:</strong> ${reserva.nombres} ${reserva.apellidos}
                </div>
                <div class="detalle-item">
                    <strong>Teléfono:</strong> ${reserva.telefono || 'No especificado'}
                </div>
                <div class="detalle-item">
                    <strong>Fecha:</strong> ${new Date(reserva.fecha_reserva).toLocaleDateString()}
                </div>
                <div class="detalle-item">
                    <strong>Hora:</strong> ${reserva.hora_reserva.substr(0,5)}
                </div>
                <div class="detalle-item">
                    <strong>Personas:</strong> ${reserva.cantidad_personas}
                </div>
                <div class="detalle-item">
                    <strong>Mesas:</strong> ${reserva.mesas_seleccionadas}
                </div>
                <div class="detalle-item">
                    <strong>Estado:</strong> <span class="estado-${reserva.estado}">${reserva.estado}</span>
                </div>
                <div class="detalle-item">
                    <strong>Observaciones:</strong> ${reserva.observaciones || 'Ninguna'}
                </div>
                <div class="detalle-item">
                    <strong>Fecha de creación:</strong> ${new Date(reserva.fecha_creacion).toLocaleString()}
                </div>
            `;
            document.getElementById('modalDetalles').style.display = 'block';
        });
}

function cerrarModal() {
    document.getElementById('modalDetalles').style.display = 'none';
}
