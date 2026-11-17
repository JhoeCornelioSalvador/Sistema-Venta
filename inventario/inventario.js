const form = document.getElementById("formInsumo");
const btnGuardar = document.getElementById("btnGuardar");
const btnCancelar = document.getElementById("btnCancelar");
const tabla = document.querySelector("#tablaInventario tbody");
const filtroCategoria = document.getElementById("filtroCategoria");

form.addEventListener("submit", e => {
  e.preventDefault(); 
  
  const nombre = document.getElementById("nombre").value;
  const cantidad = document.getElementById("cantidad").value;
  const unidad = document.getElementById("unidad").value;
  const categoria = document.getElementById("categoria").value;
  const id = document.getElementById("id").value;  

  // Validar
  if (!nombre || !cantidad || !unidad || !categoria) {
    alert("Por favor complete todos los campos.");
    return;
  }

  const datos = { nombre, cantidad, unidad, categoria, id };

  // Enviar los datos al servidor 
  fetch("inventario.php?action=guardar", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(datos)
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      form.reset();
      btnGuardar.innerHTML = '<i class="fas fa-save"></i> Guardar';
      btnGuardar.className = "btn-agregar";
      btnCancelar.style.display = "none";  
      cargarInventario();
      
      // Mostrar notificación de éxito
      showNotification('✅ Insumo guardado exitosamente', 'success');
    } else {
      showNotification('❌ Error al guardar el insumo', 'error');
    }
  })
  .catch(error => {
    console.error("Error en la solicitud:", error);
    showNotification('❌ Error de conexión', 'error');
  });
});

// Función para cargar los insumos en la tabla
function cargarInventario() {
  const categoria = filtroCategoria.value;  
  const url = categoria ? `inventario.php?action=listar&categoria=${categoria}` : "inventario.php?action=listar";

  fetch(url)
    .then(res => res.json())
    .then(data => {
      tabla.innerHTML = "";  // Limpiar la tabla
      if (data.length === 0) {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary); font-style: italic;">
            <i class="fas fa-box-open" style="font-size: 2em; margin-bottom: 10px; display: block;"></i>
            No hay insumos registrados
          </td>
        `;
        tabla.appendChild(tr);
      } else {
        data.forEach(i => {
          const tr = document.createElement("tr");
          tr.innerHTML = `
            <td><strong>#${i.id}</strong></td>
            <td>${i.nombre}</td>
            <td><span class="cantidad-badge">${i.cantidad}</span></td>
            <td>${i.unidad}</td>
            <td><span class="categoria-badge categoria-${i.categoria.toLowerCase().replace(/ /g, '-')}">${i.categoria}</span></td>
            <td>
              <div class="acciones">
                <button class="btn-editar" onclick="editarInsumo(${i.id}, '${i.nombre}', ${i.cantidad}, '${i.unidad}', '${i.categoria}')">
                  <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn-eliminar" onclick="eliminarInsumo(${i.id})">
                  <i class="fas fa-trash"></i> Eliminar
                </button>
              </div>
            </td>
          `;
          tabla.appendChild(tr);
        });
      }
    })
    .catch(error => {
      console.error("Error al cargar inventario:", error);
      showNotification('❌ Error al cargar inventario', 'error');
    });
}

filtroCategoria.addEventListener("change", () => {
  cargarInventario();  
});

// Función para editar el insumo
function editarInsumo(id, nombre, cantidad, unidad, categoria) {
  document.getElementById("id").value = id;
  document.getElementById("nombre").value = nombre;
  document.getElementById("cantidad").value = cantidad;
  document.getElementById("unidad").value = unidad;
  document.getElementById("categoria").value = categoria;

  btnGuardar.innerHTML = '<i class="fas fa-sync-alt"></i> Actualizar';
  btnGuardar.className = "btn-modificar";
  btnCancelar.style.display = "inline-block"; 
  
  document.querySelector('.form-grupo').scrollIntoView({ behavior: 'smooth' });
}

function eliminarInsumo(id) {
  if (confirm("¿Estás seguro de que deseas eliminar este insumo?")) {
    fetch(`inventario.php?action=eliminar&id=${id}`, { method: "GET" })
      .then(res => res.json())
      .then(response => {
        if (response.success) {
          limpiarFormulario();
          showNotification('✅ Insumo eliminado con éxito', 'success');
          cargarInventario(); 
        } else {
          showNotification('❌ Error al eliminar el insumo', 'error');
        }
      })
      .catch(error => {
        console.error("Error en la solicitud:", error);
        showNotification('❌ Error de conexión', 'error');
      });
  }
}

btnCancelar.addEventListener("click", () => {
  form.reset();  
  btnGuardar.innerHTML = '<i class="fas fa-save"></i> Guardar';
  btnGuardar.className = "btn-agregar";
  btnCancelar.style.display = "none";  
});

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
  
  .cantidad-badge {
    display: inline-block;
    padding: 4px 10px;
    background: var(--bg-light);
    border-radius: 8px;
    font-weight: 600;
    color: var(--text-primary);
    border: 1px solid var(--border-light);
  }
`;
document.head.appendChild(style);

document.addEventListener('DOMContentLoaded', cargarInventario);
function limpiarFormulario() {
  form.reset();
  document.getElementById("id").value = "";
  btnGuardar.innerHTML = '<i class="fas fa-save"></i> Guardar';
  btnGuardar.className = "btn-agregar";
  btnCancelar.style.display = "none";
}