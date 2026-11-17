function debugFetch(url, options) {
  console.log('Fetch a:', url);
  console.log('Opciones:', options);
  
  return fetch(url, options)
    .then(response => {
      console.log('Respuesta status:', response.status);
      console.log('Respuesta headers:', [...response.headers.entries()]);
      return response.text().then(text => {
        console.log('Respuesta text (primeros 500 chars):', text.substring(0, 500));
        try {
          const json = JSON.parse(text);
          console.log('Respuesta JSON:', json);
          return new Response(JSON.stringify(json), response);
        } catch (e) {
          console.log('No es JSON válido');
          return new Response(text, response);
        }
      });
    })
    .catch(error => {
      console.error('Error en fetch:', error);
      throw error;
    });
}
// ===== CONFIGURACIÓN GLOBAL =====
const contenedor = document.getElementById("contenedor-productos");
const tabla = document.querySelector("#tablaPedido tbody");
const totalSpan = document.getElementById("totalPedido");
const nombreClienteInput = document.getElementById("nombreCliente");
const mensajeCliente = document.getElementById("mensajeCliente");
const mensajeMesas = document.getElementById("mensajeMesas");
const btnGuardarPedido = document.getElementById("btnGuardarPedido");

let pedido = [];
let mesasSeleccionadas = [];
let categoriaActual = "platillo";

// ===== SISTEMA DE NOTIFICACIONES =====
function showNotification(message, type = 'info') {
  document.querySelectorAll('.notification').forEach(n => n.remove());
  
  const notification = document.createElement('div');
  notification.className = `notification ${type}`;
  const icons = {
    success: '<i class="fas fa-check-circle"></i>',
    error: '<i class="fas fa-exclamation-circle"></i>',
    warning: '<i class="fas fa-exclamation-triangle"></i>',
    info: '<i class="fas fa-info-circle"></i>'
  };
  
  notification.innerHTML = `
    ${icons[type] || icons.info}
    <span>${message}</span>
  `;
  
  document.body.appendChild(notification);
  
  // Auto-remover después de 4 segundos
  setTimeout(() => {
    if (notification.parentNode) {
      notification.style.animation = 'slideOutRight 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
      setTimeout(() => {
        if (notification.parentNode) {
          document.body.removeChild(notification);
        }
      }, 300);
    }
  }, 4000);
}

// ===== CARGAR PRODUCTOS =====
async function cargarProductos(categoria = "platillo") {
    categoriaActual = categoria;
    contenedor.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
            <div class="loading" style="width: 40px; height: 40px; margin: 0 auto; border: 3px solid var(--border-subtle); border-top-color: var(--accent-gold); border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="color: var(--text-muted); margin-top: 15px;">Cargando productos...</p>
        </div>
    `;
    
    try {
        const response = await fetch(`mesas_y_pedidos.php?action=listar&categoria=${encodeURIComponent(categoria)}&t=${Date.now()}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Respuesta no es JSON:', text.substring(0, 500));
            throw new Error('El servidor respondió con un formato incorrecto');
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Error al cargar productos');
        }
        
        const data = result.data || [];
        
        if (data.length === 0) {
            contenedor.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px;">
                    <i class="fas fa-utensils" style="font-size: 3em; color: var(--text-muted); margin-bottom: 15px; display: block;"></i>
                    <p style="color: var(--text-muted); font-size: 1.1em;">No hay productos en esta categoría</p>
                </div>
            `;
            return;
        }
        
        contenedor.innerHTML = "";
        
        data.forEach(producto => {
            const card = document.createElement("div");
            card.className = "producto-card";
            
            // Manejar imagen o placeholder
            let imagenHTML = '';
            if (producto.imagen && producto.imagen.trim() !== '') {
                imagenHTML = `<img src="../menu/uploads/${producto.imagen}" alt="${producto.nombre}" loading="lazy" onerror="this.onerror=null; this.parentNode.innerHTML='<div style=\\'width:100%;height:160px;background:var(--bg-light);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;\\'><i class=\\'fas fa-utensils\\' style=\\'font-size:2em;color:var(--text-muted);\\'></i></div>';">`;
            } else {
                imagenHTML = `
                    <div style="width: 100%; height: 160px; background: var(--bg-light); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-utensils" style="font-size: 2em; color: var(--text-muted);"></i>
                    </div>
                `;
            }
            
            card.innerHTML = `
                ${imagenHTML}
                <h3>${producto.nombre || 'Sin nombre'}</h3>
                <p>${producto.descripcion || 'Sin descripción'}</p>
                <span class="precio">S/ ${parseFloat(producto.precio || 0).toFixed(2)}</span>
                <button onclick="agregarAlPedido(${producto.id}, '${(producto.nombre || '').replace(/'/g, "\\'").replace(/"/g, '&quot;')}', ${producto.precio || 0})">
                    <i class="fas fa-plus"></i> Agregar al Pedido
                </button>
            `;
            contenedor.appendChild(card);
        });
        
    } catch (error) {
        console.error("Error cargando productos:", error);
        
        contenedor.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 2em; color: var(--error-color); margin-bottom: 15px; display: block;"></i>
                <p style="color: var(--error-color; margin-bottom: 10px;">${error.message || 'Error al cargar los productos'}</p>
                <button onclick="cargarProductos('${categoria}')" style="margin-top: 15px; background: var(--accent-gold); color: white; border: none; padding: 10px 20px; border-radius: var(--radius-sm); cursor: pointer;">
                    <i class="fas fa-redo"></i> Reintentar
                </button>
            </div>
        `;
        
        showNotification('Error al cargar productos: ' + (error.message || 'Error desconocido'), 'error');
    }
}

// ===== CARGAR MESAS =====
async function cargarMesas() {
    try {
        const response = await fetch(`mesas_y_pedidos.php?action=cargar_mesas&t=${Date.now()}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Respuesta de mesas no es JSON:', text.substring(0, 500));
            throw new Error('El servidor respondió con un formato incorrecto');
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Error al cargar mesas');
        }
        
        const mesas = result.data || [];
        const contenedorMesas = document.getElementById("contenedor-mesas");
        
        if (mesas.length === 0) {
            contenedorMesas.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: var(--text-muted);">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>No hay mesas disponibles</p>
                </div>
            `;
            return;
        }
        
        contenedorMesas.innerHTML = "";
        
        mesas.forEach(mesa => {
            const btnMesa = document.createElement("button");
            btnMesa.type = "button";
            btnMesa.className = "mesa-btn";
            btnMesa.dataset.mesaId = mesa.id;
            btnMesa.dataset.mesaNumero = mesa.numero_mesa;
            
            // Determinar clase según estado
            if (mesa.estado === 'ocupada') {
                btnMesa.classList.add('ocupada');
                btnMesa.title = `Mesa ${mesa.numero_mesa} - Ocupada (Piso ${mesa.piso})`;
                btnMesa.disabled = true;
            } else if (mesa.estado === 'reservada') {
                btnMesa.classList.add('reservada');
                btnMesa.title = `Mesa ${mesa.numero_mesa} - Reservada (Piso ${mesa.piso})`;
                btnMesa.disabled = true;
            } else {
                // Mesa libre
                btnMesa.onclick = () => toggleMesa(mesa.id, mesa.numero_mesa);
                btnMesa.title = `Mesa ${mesa.numero_mesa} - Libre (Piso ${mesa.piso})`;
                
                // Verificar si ya está seleccionada
                if (mesasSeleccionadas.some(m => m.id === mesa.id)) {
                    btnMesa.classList.add('seleccionada');
                }
            }
            
            // Información adicional
            let infoText = `P${mesa.piso}`;
            if (mesa.ubicacion_especial === 'balcon') {
                infoText += ' | Balcón';
            }
            
            btnMesa.innerHTML = `
                <strong>Mesa ${mesa.numero_mesa}</strong>
                <span class="info-mesa">${infoText}</span>
            `;
            
            contenedorMesas.appendChild(btnMesa);
        });
        
    } catch (error) {
        console.error('Error cargando mesas:', error);
        
        const contenedorMesas = document.getElementById("contenedor-mesas");
        if (contenedorMesas) {
            contenedorMesas.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: var(--error-color);">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error al cargar mesas</p>
                    <button onclick="cargarMesas()" style="margin-top: 10px; background: var(--accent-gold); color: white; border: none; padding: 5px 15px; border-radius: var(--radius-sm); cursor: pointer; font-size: 0.9em;">
                        <i class="fas fa-redo"></i> Reintentar
                    </button>
                </div>
            `;
        }
        
        showNotification('Error al cargar mesas: ' + (error.message || 'Error desconocido'), 'error');
    }
}

// ===== GESTIÓN DE MESAS =====
function toggleMesa(mesaId, numeroMesa) {
  const index = mesasSeleccionadas.findIndex(m => m.id === mesaId);
  
  if (index === -1) {
    // Agregar mesa
    if (mesasSeleccionadas.length >= 3) {
      mensajeMesas.textContent = "Máximo 3 mesas permitidas";
      mensajeMesas.style.display = "block";
      mensajeMesas.className = "error";
      return;
    }
    
    mesasSeleccionadas.push({ 
      id: mesaId, 
      numero: numeroMesa,
      timestamp: Date.now()
    });
  } else {
    // Quitar mesa
    mesasSeleccionadas.splice(index, 1);
  }
  
  actualizarVisualizacionMesas();
  mensajeMesas.style.display = "none";
}

function actualizarVisualizacionMesas() {
  // Resetear todos los botones
  document.querySelectorAll('.mesa-btn:not(.ocupada):not(.reservada)').forEach(btn => {
    btn.classList.remove('seleccionada');
  });
  
  // Marcar seleccionadas
  mesasSeleccionadas.forEach(mesa => {
    const btn = document.querySelector(`.mesa-btn[data-mesa-id="${mesa.id}"]:not(.ocupada):not(.reservada)`);
    if (btn) {
      btn.classList.add('seleccionada');
    }
  });
}

// ===== GESTIÓN DEL PEDIDO =====
function agregarAlPedido(id, nombre, precio) {
  const existente = pedido.find(item => item.id === id);
  
  if (existente) {
    existente.cantidad += 1;
  } else {
    pedido.push({
      id: id,
      nombre: nombre,
      cantidad: 1,
      precio: parseFloat(precio),
      timestamp: Date.now()
    });
  }
  
  renderPedido();
  
  // Feedback visual
  const button = event.target.closest('button');
  if (button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i> ¡Agregado!';
    button.style.background = 'linear-gradient(135deg, var(--success-color), #2f855a)';
    
    setTimeout(() => {
      button.innerHTML = originalText;
      button.style.background = '';
    }, 1000);
  }
}

function eliminarDelPedido(index) {
  if (confirm('¿Eliminar este producto del pedido?')) {
    pedido.splice(index, 1);
    renderPedido();
    showNotification('Producto eliminado del pedido', 'warning');
  }
}

function renderPedido() {
  tabla.innerHTML = "";
  
  if (pedido.length === 0) {
    tabla.innerHTML = `
      <tr>
        <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">
          <i class="fas fa-shopping-cart" style="font-size: 2em; margin-bottom: 10px; display: block;"></i>
          <p>No hay productos en el pedido</p>
        </td>
      </tr>
    `;
    totalSpan.textContent = "0.00";
    return;
  }
  
  let total = 0;
  
  pedido.forEach((item, index) => {
    const subtotal = item.cantidad * item.precio;
    total += subtotal;
    
    const row = document.createElement("tr");
    row.innerHTML = `
      <td style="text-align: left; font-weight: 500;">${item.nombre}</td>
      <td>
        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
          <button onclick="actualizarCantidad(${index}, -1)" style="background: var(--text-muted); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-minus" style="font-size: 10px;"></i>
          </button>
          <span style="min-width: 30px; text-align: center;">${item.cantidad}</span>
          <button onclick="actualizarCantidad(${index}, 1)" style="background: var(--accent-gold); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-plus" style="font-size: 10px;"></i>
          </button>
        </div>
      </td>
      <td style="color: var(--text-primary); font-weight: 500;">S/ ${item.precio.toFixed(2)}</td>
      <td style="color: var(--success-color); font-weight: 600;">S/ ${subtotal.toFixed(2)}</td>
      <td>
        <button class="btn-eliminar" onclick="eliminarDelPedido(${index})" title="Eliminar producto">
          <i class="fas fa-trash"></i>
        </button>
      </td>
    `;
    tabla.appendChild(row);
  });
  
  totalSpan.textContent = total.toFixed(2);
}

function actualizarCantidad(index, cambio) {
  const item = pedido[index];
  const nuevaCantidad = item.cantidad + cambio;
  
  if (nuevaCantidad < 1) {
    eliminarDelPedido(index);
    return;
  }
  
  item.cantidad = nuevaCantidad;
  renderPedido();
}

// ===== GUARDAR PEDIDO =====
async function guardarPedido() {
  const nombreCliente = nombreClienteInput.value.trim();
  const errores = [];
  mensajeCliente.style.display = "none";
  mensajeMesas.style.display = "none";
  
  if (!nombreCliente) {
    errores.push("Ingresa el nombre del cliente");
    mensajeCliente.textContent = "Ingresa el nombre del cliente antes de guardar el pedido";
    mensajeCliente.style.display = "block";
    mensajeCliente.className = "error";
  }
  
  if (mesasSeleccionadas.length === 0) {
    errores.push("Selecciona al menos una mesa");
    mensajeMesas.textContent = "Selecciona al menos 1 mesa (máximo 3)";
    mensajeMesas.style.display = "block";
    mensajeMesas.className = "error";
  }
  
  if (pedido.length === 0) {
    errores.push("Agrega productos al pedido");
    mensajeCliente.textContent = "No hay productos en el pedido";
    mensajeCliente.style.display = "block";
    mensajeCliente.className = "error";
  }
  
  if (errores.length > 0) {
    showNotification('Corrige los errores antes de guardar', 'error');
    return;
  }
  
  btnGuardarPedido.disabled = true;
  const originalText = btnGuardarPedido.innerHTML;
  btnGuardarPedido.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
  
  try {
    const datos = {
      nombreCliente: nombreCliente,
      pedido: pedido.map(item => ({
        id: item.id,
        nombre: item.nombre,
        cantidad: item.cantidad,
        precio: item.precio
      })),
      mesas: mesasSeleccionadas.map(mesa => ({
        id: mesa.id,
        numero: mesa.numero
      }))
    };
    
    console.log('Enviando datos:', datos);
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);
    
    const response = await fetch("mesas_y_pedidos.php?action=guardar", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "Accept": "application/json"
      },
      body: JSON.stringify(datos),
      signal: controller.signal
    });
    
    clearTimeout(timeoutId);
    
    console.log('Respuesta recibida, status:', response.status);
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      const text = await response.text();
      console.error('Respuesta no es JSON:', text.substring(0, 200)); 
      throw new Error(`El servidor respondió con: ${response.status} ${response.statusText}`);
    }
    
    const result = await response.json();
    console.log('Resultado parseado:', result);
    
    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`);
    }
    
    if (result.success) {
      limpiarPedido();
      showNotification(result.message || 'Pedido guardado exitosamente', 'success');
      
      // Recargar mesas para actualizar estado
      cargarMesas();
    } else {
      throw new Error(result.error || 'Error desconocido al guardar el pedido');
    }
    
  } catch (error) {
    console.error('Error completo al guardar pedido:', error);
    
    let errorMessage = 'Error al guardar el pedido';
    
    if (error.name === 'AbortError') {
      errorMessage = 'Tiempo de espera agotado. Intenta nuevamente.';
    } else if (error.message.includes('404')) {
      errorMessage = 'No se encontró el archivo PHP. Verifica la ruta.';
    } else if (error.message.includes('500')) {
      errorMessage = 'Error interno del servidor. Contacta al administrador.';
    } else {
      errorMessage = error.message || 'Error de conexión con el servidor';
    }
    
    showNotification(errorMessage, 'error');
    
  } finally {
    btnGuardarPedido.disabled = false;
    btnGuardarPedido.innerHTML = originalText;
  }
}

function limpiarPedido() {
  nombreClienteInput.value = "";
  pedido = [];
  mesasSeleccionadas = [];
  renderPedido();
  actualizarVisualizacionMesas();
  
  // Ocultar mensajes
  mensajeCliente.style.display = "none";
  mensajeMesas.style.display = "none";
  setTimeout(() => {
    nombreClienteInput.focus();
  }, 100);
}

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll(".tab").forEach(btn => {
    btn.addEventListener("click", e => {
      document.querySelectorAll(".tab").forEach(b => b.classList.remove("active"));
      e.target.classList.add("active");
      const categoria = e.target.dataset.cat;
      cargarProductos(categoria);
    });
  });
  
  btnGuardarPedido.addEventListener("click", guardarPedido);
  nombreClienteInput.addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
      e.preventDefault();
      guardarPedido();
    }
  });
  
  cargarProductos("platillo");
  cargarMesas();
  
  setInterval(cargarMesas, 30000);
  setTimeout(() => {
    showNotification('Selecciona productos, mesas y completa el pedido', 'info');
  }, 1000);
});

// ===== FUNCIONES GLOBALES =====
window.agregarAlPedido = agregarAlPedido;
window.eliminarDelPedido = eliminarDelPedido;
window.actualizarCantidad = actualizarCantidad;
window.toggleMesa = toggleMesa;
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);