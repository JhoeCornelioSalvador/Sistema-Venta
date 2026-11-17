const form = document.getElementById("formPlato");
const btnCancelar = document.getElementById("btnCancelar");
const inputImagen = document.getElementById("imagen");
const nombreArchivo = document.getElementById("nombre-archivo");
const tablaBody = document.getElementById("tablaMenuBody");

// Configuración de notificaciones
function showNotification(message, type) {
  const notification = document.createElement('div');
  notification.className = `notification notification-${type}`;
  notification.innerHTML = message;
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 10px;
    background: ${type === 'success' ? 'var(--success-color)' : 'var(--error-color)'};
    color: white;
    font-weight: 600;
    z-index: 1000;
    animation: slideInRight 0.3s ease-out;
    box-shadow: var(--shadow-medium);
  `;
  
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.style.animation = 'slideOutRight 0.3s ease-out';
    setTimeout(() => {
      document.body.removeChild(notification);
    }, 300);
  }, 3000);
}

// Añadir estilos de animación para notificaciones
const style = document.createElement('style');
style.textContent = `
  @keyframes slideInRight {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  @keyframes slideOutRight {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(100%);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);

inputImagen.addEventListener("change", () => {
  nombreArchivo.textContent = inputImagen.files.length
    ? `📁 ${inputImagen.files[0].name}`
    : "";
});


function cargarMenu() {
  fetch("menu.php?action=listar")
    .then(res => res.json())
    .then(data => {
      tablaBody.innerHTML = "";
      
      if (data.length === 0) {
        tablaBody.innerHTML = `
          <tr>
            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary); font-style: italic;">
              <i class="fas fa-utensils" style="font-size: 2em; margin-bottom: 10px; display: block;"></i>
              No hay platillos en el menú
            </td>
          </tr>
        `;
      } else {
        data.forEach(plato => {
          const tr = document.createElement("tr");
          const categoriaClass = `categoria-${plato.categoria}`;
          const categoriaText = plato.categoria.charAt(0).toUpperCase() + plato.categoria.slice(1);
          const descripcion = plato.descripcion || "<em style='color: var(--text-secondary); font-size: 0.9em;'>Sin descripción</em>";
          
          tr.innerHTML = `
            <td style="text-align: center;">
              ${plato.imagen ? 
                `<img src="uploads/${plato.imagen}" alt="${plato.nombre}" title="${plato.nombre}" 
                     style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover; border: 2px solid var(--border-light);">` : 
                '<i class="fas fa-image" style="font-size: 1.8em; color: var(--text-secondary);" title="Sin imagen"></i>'
              }
            </td>
            <td>
              <strong style="font-size: 0.95em;">${plato.nombre}</strong>
            </td>
            <td style="text-align: center;">
              <span class="categoria-badge ${categoriaClass}" title="${categoriaText}">
                ${categoriaText}
              </span>
            </td>
            <td style="text-align: center;">
              <span class="precio-destacado">S/ ${parseFloat(plato.precio).toFixed(2)}</span>
            </td>
            <td class="descripcion-celda" title="${plato.descripcion || 'Sin descripción'}">
              ${descripcion.length > 100 ? descripcion.substring(0, 100) + '...' : descripcion}
            </td>
            <td>
              <div class="acciones">
                <button class="btn-editar" onclick="editarPlato(${plato.id}, '${plato.nombre.replace(/'/g, "\\'")}', '${plato.categoria}', ${plato.precio}, '${(plato.descripcion || "").replace(/'/g, "\\'")}')" title="Editar platillo">
                  <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn-eliminar" onclick="eliminarPlato(${plato.id})" title="Eliminar platillo">
                  <i class="fas fa-trash"></i> Eliminar
                </button>
              </div>
            </td>
          `;
          tablaBody.appendChild(tr);
        });
      }
    })
    .catch(error => {
      console.error("Error al cargar menú:", error);
      showNotification('❌ Error al cargar el menú', 'error');
      tablaBody.innerHTML = `
        <tr>
          <td colspan="6" style="text-align: center; padding: 40px; color: var(--error-color);">
            <i class="fas fa-exclamation-triangle" style="font-size: 2em; margin-bottom: 10px; display: block;"></i>
            Error al cargar los datos
          </td>
        </tr>
      `;
    });
}

// Evento para enviar el formulario
form.addEventListener("submit", e => {
  e.preventDefault();
  const formData = new FormData(form);

  fetch("menu.php?action=guardar", { 
    method: "POST", 
    body: formData 
  })
    .then(res => res.json())
    .then(response => {
      if (response.success) {
        form.reset();
        nombreArchivo.textContent = "";
        btnCancelar.style.display = "none";
        document.getElementById("id").value = "";
        document.getElementById("insumosContainer").innerHTML = "";
        cargarMenu();
        showNotification('✅ Platillo guardado exitosamente', 'success');
      } else {
        showNotification('❌ Error al guardar el platillo', 'error');
      }
    })
    .catch(error => {
      console.error("Error:", error);
      showNotification('❌ Error de conexión', 'error');
    });
});

// Función para editar un platillo
function editarPlato(id, nombre, categoria, precio, descripcion) {
  document.getElementById("id").value = id;
  document.getElementById("nombre").value = nombre;
  document.getElementById("categoria").value = categoria;
  document.getElementById("precio").value = precio;
  document.getElementById("descripcion").value = descripcion;
  btnCancelar.style.display = "inline-block";
  document.querySelector('.gestion-menu').scrollIntoView({ 
    behavior: 'smooth', 
    block: 'start' 
  });
  showNotification('✏️ Modo edición activado', 'warning');
}

// Evento para cancelar edición
btnCancelar.addEventListener("click", () => {
  form.reset();
  nombreArchivo.textContent = "";
  document.getElementById("id").value = "";
  document.getElementById("insumosContainer").innerHTML = "";
  btnCancelar.style.display = "none";
  showNotification('↩️ Edición cancelada', 'warning');
});

// Función para eliminar un platillo
function eliminarPlato(id) {
  if (confirm("¿Seguro que desea eliminar este platillo del menú?")) {
    fetch(`menu.php?action=eliminar&id=${id}`)
      .then(res => res.json())
      .then(response => {
        if (response.success) {
          cargarMenu();
          showNotification('🗑️ Platillo eliminado exitosamente', 'success');
        } else {
          showNotification('❌ Error al eliminar el platillo', 'error');
        }
      })
      .catch(error => {
        console.error("Error:", error);
        showNotification('❌ Error de conexión', 'error');
      });
  }
}
let insumosData = [];

// Cargar insumos desde inventario
function cargarInsumosDisponibles() {
  fetch("../inventario/inventario.php?action=listar")
    .then(res => res.json())
    .then(data => { 
      insumosData = data;
      if (data.length === 0) {
        showNotification('ℹ️ No hay insumos disponibles en el inventario', 'warning');
      }
    })
    .catch(error => {
      console.error("Error al cargar insumos:", error);
      showNotification('❌ Error al cargar insumos', 'error');
    });
}

// Función para crear una fila de insumo
function crearFilaInsumo(insumoId = "", cantidad = "") {
  const contenedor = document.getElementById("insumosContainer");
  const emptyMsg = contenedor.querySelector('.empty-message');
  if (emptyMsg) emptyMsg.remove();
  
  const div = document.createElement("div");
  div.className = "fila fila-insumo";
  
  const select = document.createElement("select");
  select.name = "insumos[]";
  select.required = true;

  const opcionDefault = document.createElement("option");
  opcionDefault.textContent = "Seleccionar insumo...";
  opcionDefault.value = "";
  select.appendChild(opcionDefault);

  insumosData.forEach(i => {
    const opt = document.createElement("option");
    opt.value = i.id;
    opt.textContent = `${i.nombre} (${i.cantidad} ${i.unidad})`;
    if (i.id == insumoId) opt.selected = true;
    select.appendChild(opt);
  });

  const inputCant = document.createElement("input");
  inputCant.type = "number";
  inputCant.name = "cantidades[]";
  inputCant.step = "0.01";
  inputCant.placeholder = "Cantidad usada";
  inputCant.required = true;
  inputCant.value = cantidad;
  inputCant.min = "0.01";

  const btnEliminar = document.createElement("button");
  btnEliminar.type = "button";
  btnEliminar.innerHTML = '<i class="fas fa-trash"></i>';
  btnEliminar.className = "btn-eliminar-insumo";


  btnEliminar.onclick = () => {
    div.remove();
    if (contenedor.children.length === 0) {
      const emptyDiv = document.createElement("div");
      emptyDiv.className = "empty-message";
      emptyDiv.style.cssText = `
        text-align: center;
        padding: 20px;
        color: var(--text-secondary);
        font-style: italic;
        border: 2px dashed var(--border-light);
        border-radius: 8px;
        margin-bottom: 10px;
      `;
      emptyDiv.innerHTML = '<i class="fas fa-box-open" style="font-size: 1.5em; margin-bottom: 8px; display: block;"></i>No hay insumos agregados';
      contenedor.appendChild(emptyDiv);
    }
  };

  div.append(select, inputCant, btnEliminar);
  contenedor.appendChild(div);
}

// Evento para agregar insumo
document.getElementById("btnAgregarInsumo").addEventListener("click", () => {
  if (insumosData.length === 0) {
    showNotification('⚠️ Primero debe cargar insumos en el inventario', 'warning');
    return;
  }
  crearFilaInsumo();
});

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', () => {
  cargarMenu();
  cargarInsumosDisponibles();
  
  // Agregar una fila de insumo por defecto después de cargar los insumos
  setTimeout(() => {
    if (insumosData.length > 0) {
      crearFilaInsumo();
    }
  }, 1000);
});