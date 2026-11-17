cargarSidebar("mesero");

const tablaPedidos = document.querySelector("#tablaPedidos tbody");
const detalleCard = document.getElementById("detallePedido");
const listaPlatos = document.getElementById("listaPlatos");
const totalGeneral = document.getElementById("totalGeneral");
const qrYape = document.getElementById("qrYape");
const confirmacionPago = document.getElementById("confirmacionPago");
const toast = document.getElementById("toast");

let clienteVisible = null;
let clienteActual = "";

// Mostrar notificación
function mostrarToast(mensaje, tipo = "info") {
  toast.textContent = mensaje;
  toast.className = "toast show";
  if (tipo === "error") toast.classList.add("error");

  setTimeout(() => {
    toast.className = "toast";
  }, 3000);
}

// Cargar pedidos pendientes
function cargarPedidos() {
  fetch("facturacion.php?action=listar")
    .then(res => res.json())
    .then(data => {
      tablaPedidos.innerHTML = "";
      if (!data || data.length === 0) {
        tablaPedidos.innerHTML = "<tr><td colspan='5'>✅ No hay pedidos pendientes.</td></tr>";
        detalleCard.style.display = "none";
        return;
      }

      data.forEach((p, i) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${i + 1}</td>
          <td>${p.cliente}</td>
          <td>${new Date(p.fecha_pedido).toLocaleString()}</td>
          <td>${p.estado}</td>
          <td><button class="btn-ver" id="btn-${i}" onclick="toggleDetalle('${p.cliente}', this)">Ver detalle</button></td>
        `;
        tablaPedidos.appendChild(tr);
      });
    });
}

// Ocultar detalle
function toggleDetalle(cliente, boton) {
  if (clienteVisible === cliente) {
    detalleCard.style.display = "none";
    clienteVisible = null;
    boton.textContent = "Ver detalle";
    boton.classList.remove("ocultar");
    return;
  }

  clienteVisible = cliente;
  clienteActual = cliente;
  boton.textContent = "Ocultar detalle";
  boton.classList.add("ocultar");
  document.querySelectorAll(".btn-ver").forEach(btn => {
    if (btn !== boton) {
      btn.textContent = "Ver detalle";
      btn.classList.remove("ocultar");
    }
  });

  fetch("facturacion.php?action=detalle&cliente=" + encodeURIComponent(cliente))
    .then(res => res.json())
    .then(data => {
      detalleCard.style.display = "block";
      document.getElementById("detalleCliente").textContent = cliente;
      listaPlatos.innerHTML = "";
      totalGeneral.textContent = "";

      let total = 0;
      data.forEach(item => {
        const precio = parseFloat(item.precio);
        total += precio;
        const div = document.createElement("div");
        div.classList.add("plato-item");
        div.textContent = `🍽️ ${item.nombre} — S/ ${precio.toFixed(2)}`;
        listaPlatos.appendChild(div);
      });

      totalGeneral.textContent = "💵 Total a pagar: S/ " + total.toFixed(2);
      qrYape.style.display = "none";
      confirmacionPago.style.display = "none";
      document.querySelectorAll("input[name='metodoPago']").forEach(r => r.checked = false);
    });
}

// Mostrar QR
document.querySelectorAll("input[name='metodoPago']").forEach(radio => {
  radio.addEventListener("change", e => {
    if (e.target.value === "yape") qrYape.style.display = "block";
    else qrYape.style.display = "none";
    confirmacionPago.style.display = "block";
  });
});

// Confirmar pago
function confirmarPago(confirmado) {
  if (!confirmado) {
    mostrarToast("El cliente aún no ha pagado.", "error");
    return;
  }

  fetch("facturacion.php?action=facturar", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ cliente: clienteActual })
  })
    .then(res => res.json())
    .then(() => {
      mostrarToast("Pedido facturado correctamente ✅");
      cargarPedidos();
      detalleCard.style.display = "none";
    })
    .catch(() => {
      mostrarToast("Error al facturar el pedido.", "error");
    });
}

cargarPedidos();
