let datosPedidos = [];
let datosPopulares = [];
document.addEventListener('DOMContentLoaded', function() {
    cargarSidebar("admin");
    configurarEventos();
    cargarReportes();
});
function configurarEventos() {
    document.getElementById('filtroRango').addEventListener('change', function() {
        const rangoPersonalizado = document.getElementById('rangoPersonalizado');
        rangoPersonalizado.style.display = this.value === 'personalizado' ? 'block' : 'none';
    });
    document.getElementById('btnAplicarFiltros').addEventListener('click', cargarReportes);

    // Botones de exportación
    document.querySelectorAll('.btn-exportar').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabla = this.dataset.tabla;
            const formato = this.dataset.formato;
            exportarReporte(tabla, formato);
        });
    });

    // Fechas
    const hoy = new Date().toISOString().split('T')[0];
    const haceUnaSemana = new Date();
    haceUnaSemana.setDate(haceUnaSemana.getDate() - 7);
    const fechaHaceUnaSemana = haceUnaSemana.toISOString().split('T')[0];

    document.getElementById('fechaDesde').value = fechaHaceUnaSemana;
    document.getElementById('fechaHasta').value = hoy;
}

// Cargar reportes
function cargarReportes() {
    const filtros = obtenerFiltros();
    
    fetch(`reportes.php?action=cargar_reportes&${new URLSearchParams(filtros)}`)
        .then(response => response.json())
        .then(data => {
            datosPedidos = data.pedidos || [];
            datosPopulares = data.populares || [];
            
            renderizarTablaPedidos();
            renderizarTablaPopulares();
        })
        .catch(error => {
            console.error('Error cargando reportes:', error);
            mostrarToast('Error al cargar los reportes', 'error');
        });
}

// Obtener filtros aplicados
function obtenerFiltros() {
    const rango = document.getElementById('filtroRango').value;
    const categoria = document.getElementById('filtroCategoria').value;
    
    let fechaDesde, fechaHasta;
    
    switch(rango) {
        case 'hoy':
            fechaDesde = fechaHasta = new Date().toISOString().split('T')[0];
            break;
        case 'semana':
            fechaHasta = new Date().toISOString().split('T')[0];
            const hace7Dias = new Date();
            hace7Dias.setDate(hace7Dias.getDate() - 7);
            fechaDesde = hace7Dias.toISOString().split('T')[0];
            break;
        case 'mes':
            fechaHasta = new Date().toISOString().split('T')[0];
            const hace30Dias = new Date();
            hace30Dias.setDate(hace30Dias.getDate() - 30);
            fechaDesde = hace30Dias.toISOString().split('T')[0];
            break;
        case 'personalizado':
            fechaDesde = document.getElementById('fechaDesde').value;
            fechaHasta = document.getElementById('fechaHasta').value;
            break;
    }
    
    return {
        fecha_desde: fechaDesde,
        fecha_hasta: fechaHasta,
        categoria: categoria
    };
}

// Renderizar tabla de pedidos
function renderizarTablaPedidos() {
    const tbody = document.querySelector('#tablaPedidosPagados tbody');
    const totalGeneral = document.getElementById('totalGeneral');
    
    tbody.innerHTML = '';
    
    if (datosPedidos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px; color: #666;">No hay pedidos para los filtros seleccionados</td></tr>';
        totalGeneral.textContent = 'S/ 0.00';
        return;
    }
    
    let total = 0;
    
    datosPedidos.forEach(pedido => {
        const subtotal = pedido.cantidad * pedido.precio;
        total += subtotal;
        
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${formatearFecha(pedido.fecha_pedido)}</td>
            <td>${pedido.cliente || 'N/A'}</td>
            <td>${pedido.nombre}</td>
            <td>${pedido.cantidad}</td>
            <td>S/ ${parseFloat(pedido.precio).toFixed(2)}</td>
            <td>S/ ${subtotal.toFixed(2)}</td>
            <td><span class="categoria-badge categoria-${pedido.categoria}">${pedido.categoria}</span></td>
        `;
        tbody.appendChild(fila);
    });
    
    totalGeneral.textContent = `S/ ${total.toFixed(2)}`;
}

// Renderizar tabla de populares
function renderizarTablaPopulares() {
    const tbody = document.querySelector('#tablaPlatillosPopulares tbody');
    
    tbody.innerHTML = '';
    
    if (datosPopulares.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px; color: #666;">No hay datos de platillos populares</td></tr>';
        return;
    }
    
    // Calcular total para porcentajes
    const totalVendido = datosPopulares.reduce((sum, item) => sum + item.veces_vendido, 0);
    
    datosPopulares.forEach((item, index) => {
        const porcentaje = totalVendido > 0 ? ((item.veces_vendido / totalVendido) * 100).toFixed(1) : 0;
        
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td><span class="ranking-badge">${index + 1}</span></td>
            <td>${item.nombre}</td>
            <td>${item.veces_vendido}</td>
            <td>S/ ${parseFloat(item.ingreso_total).toFixed(2)}</td>
            <td>${porcentaje}%</td>
        `;
        tbody.appendChild(fila);
    });
}

// Exportar reportes
function exportarReporte(tipo, formato) {
    if (formato === 'excel') {
        exportarExcel(tipo);
    } else if (formato === 'pdf') {
        exportarPDF(tipo);
    }
}

// Función mejorada para exportar PDF
function exportarPDF(tipo) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Configuración del documento
    const pageWidth = doc.internal.pageSize.getWidth();
    const margin = 15;
    
    let titulo, datos, columnas, totalGeneral = 0;
    
    // Header del documento
    doc.setFillColor(42, 55, 72);
    doc.rect(0, 0, pageWidth, 50, 'F');
    
    // Logo y título
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(20);
    doc.setFont('helvetica', 'bold');
    doc.text('EL GUSTO MARINO', pageWidth / 2, 20, { align: 'center' });
    
    doc.setFontSize(14);
    doc.setFont('helvetica', 'normal');
    if (tipo === 'pedidos') {
        titulo = 'REPORTE DE VENTAS - HISTORIAL COMPLETO';
        doc.text(titulo, pageWidth / 2, 30, { align: 'center' });
    } else {
        titulo = 'REPORTE DE PRODUCTOS - ANÁLISIS DE VENTAS';
        doc.text(titulo, pageWidth / 2, 30, { align: 'center' });
    }
    
    // Fecha de generación
    doc.setFontSize(10);
    doc.text(`Generado: ${new Date().toLocaleDateString('es-ES', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })}`, pageWidth / 2, 40, { align: 'center' });
    
    // Preparar datos según el tipo
    let startY = 60;
    
    if (tipo === 'pedidos') {
        columnas = [
            { header: 'Fecha', dataKey: 'fecha' },
            { header: 'Cliente', dataKey: 'cliente' },
            { header: 'Producto', dataKey: 'producto' },
            { header: 'Cant.', dataKey: 'cantidad' },
            { header: 'P. Unit.', dataKey: 'precio' },
            { header: 'Total', dataKey: 'total' },
            { header: 'Categoría', dataKey: 'categoria' }
        ];
        
        datos = datosPedidos.map(pedido => {
            const subtotal = pedido.cantidad * pedido.precio;
            totalGeneral += subtotal;
            return {
                fecha: formatearFecha(pedido.fecha_pedido),
                cliente: pedido.cliente || 'N/A',
                producto: pedido.nombre,
                cantidad: pedido.cantidad.toString(),
                precio: `S/ ${parseFloat(pedido.precio).toFixed(2)}`,
                total: `S/ ${subtotal.toFixed(2)}`,
                categoria: pedido.categoria.toUpperCase()
            };
        });
        
    } else {
        const totalVendido = datosPopulares.reduce((sum, item) => sum + item.veces_vendido, 0);
        columnas = [
            { header: '#', dataKey: 'ranking' },
            { header: 'Producto', dataKey: 'producto' },
            { header: 'Unid. Vendidas', dataKey: 'vendidas' },
            { header: 'Ingresos Totales', dataKey: 'ingresos' },
            { header: 'Participación %', dataKey: 'participacion' }
        ];
        
        datos = datosPopulares.map((item, index) => {
            const porcentaje = totalVendido > 0 ? ((item.veces_vendido / totalVendido) * 100).toFixed(1) : 0;
            return {
                ranking: (index + 1).toString(),
                producto: item.nombre,
                vendidas: item.veces_vendido.toString(),
                ingresos: `S/ ${parseFloat(item.ingreso_total).toFixed(2)}`,
                participacion: `${porcentaje}%`
            };
        });
    }
    
    // Configuración de la tabla
    doc.autoTable({
        head: [columnas.map(col => col.header)],
        body: datos.map(row => columnas.map(col => row[col.dataKey])),
        startY: startY,
        margin: { top: startY },
        headStyles: {
            fillColor: [214, 158, 46],
            textColor: 255,
            fontStyle: 'bold',
            fontSize: 10
        },
        bodyStyles: {
            fontSize: 9,
            textColor: [42, 55, 72]
        },
        alternateRowStyles: {
            fillColor: [245, 247, 250]
        },
        styles: {
            cellPadding: 4,
            lineColor: [200, 200, 200],
            lineWidth: 0.1
        },
        columnStyles: {
            0: { cellWidth: 25 },
            ...(tipo === 'pedidos' ? {
                1: { cellWidth: 30 },
                2: { cellWidth: 40 },
                3: { cellWidth: 20 },
                4: { cellWidth: 25 },
                5: { cellWidth: 25 },
                6: { cellWidth: 25 }
            } : {
                1: { cellWidth: 50 },
                2: { cellWidth: 30 },
                3: { cellWidth: 30 },
                4: { cellWidth: 25 }
            })
        },
        didDrawPage: function(data) {
            // Footer en cada página
            doc.setFontSize(8);
            doc.setTextColor(100);
            doc.text(
                `Página ${doc.internal.getNumberOfPages()}`,
                pageWidth / 2,
                doc.internal.pageSize.getHeight() - 10,
                { align: 'center' }
            );
        }
    });
    
    // Agregar total general para reporte de ventas
    if (tipo === 'pedidos' && datosPedidos.length > 0) {
        const finalY = doc.lastAutoTable.finalY + 10;
        doc.setFontSize(10);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(42, 55, 72);
        doc.text(`TOTAL GENERAL: S/ ${totalGeneral.toFixed(2)}`, margin, finalY);
    }
    
    // Información de la empresa en la última página
    doc.setFontSize(8);
    doc.setTextColor(100);
    doc.text(
        'El Gusto Marino - Sistema de Gestión de Restaurante',
        pageWidth / 2,
        doc.internal.pageSize.getHeight() - 5,
        { align: 'center' }
    );
    const fecha = new Date().toISOString().split('T')[0];
    const nombreArchivo = `${tipo === 'pedidos' ? 'reporte_ventas' : 'reporte_productos'}_${fecha}.pdf`;
    doc.save(nombreArchivo);
    
    mostrarToast('Reporte PDF generado correctamente', 'success');
}

// Función mejorada para exportar Excel
function exportarExcel(tipo) {
    let datos, nombreArchivo, opciones;
    
    if (tipo === 'pedidos') {
        let totalGeneral = 0;
        datos = datosPedidos.map((pedido, index) => {
            const subtotal = pedido.cantidad * pedido.precio;
            totalGeneral += subtotal;
            return {
                'N°': index + 1,
                'Fecha': formatearFecha(pedido.fecha_pedido),
                'Cliente': pedido.cliente || 'N/A',
                'Producto': pedido.nombre,
                'Cantidad': pedido.cantidad,
                'Precio Unitario': parseFloat(pedido.precio),
                'Total': subtotal,
                'Categoría': pedido.categoria.toUpperCase()
            };
        });
        
        // Agregar fila de total
        datos.push({
            'N°': '',
            'Fecha': '',
            'Cliente': '',
            'Producto': '',
            'Cantidad': 'TOTAL GENERAL:',
            'Precio Unitario': '',
            'Total': totalGeneral,
            'Categoría': ''
        });
        
        nombreArchivo = 'reporte_ventas_detallado';
        opciones = {
            '!cols': [
                { wch: 5 }, 
                { wch: 12 }, 
                { wch: 20 }, 
                { wch: 25 }, 
                { wch: 10 },
                { wch: 12 }, 
                { wch: 12 }, 
                { wch: 15 }  
            ]
        };
        
    } else {
        const totalVendido = datosPopulares.reduce((sum, item) => sum + item.veces_vendido, 0);
        const totalIngresos = datosPopulares.reduce((sum, item) => sum + parseFloat(item.ingreso_total), 0);
        
        datos = datosPopulares.map((item, index) => {
            const porcentaje = totalVendido > 0 ? ((item.veces_vendido / totalVendido) * 100).toFixed(1) : 0;
            return {
                'Ranking': index + 1,
                'Producto': item.nombre,
                'Unidades Vendidas': item.veces_vendido,
                'Ingresos Totales': parseFloat(item.ingreso_total),
                'Participación %': parseFloat(porcentaje)
            };
        });
        
        // Agregar filas de resumen
        datos.push(
            {},
            {
                'Ranking': 'TOTALES:',
                'Producto': '',
                'Unidades Vendidas': totalVendido,
                'Ingresos Totales': totalIngresos,
                'Participación %': '100%'
            }
        );
        
        nombreArchivo = 'analisis_productos';
        opciones = {
            '!cols': [
                { wch: 8 },
                { wch: 30 },
                { wch: 15 },
                { wch: 15 },
                { wch: 12 }
            ]
        };
    }
    
    if (datos.length === 0) {
        mostrarToast('No hay datos para exportar', 'warning');
        return;
    }
    
    // Crear libro de trabajo
    const worksheet = XLSX.utils.json_to_sheet(datos.slice(0, -2));
    worksheet['!cols'] = opciones['!cols'];
    if (tipo === 'pedidos') {
        const range = XLSX.utils.decode_range(worksheet['!ref']);
        const totalRow = range.e.r + 2;
        XLSX.utils.sheet_add_aoa(worksheet, [[
            '', '', '', '', 'TOTAL GENERAL:', '', datosPedidos.reduce((sum, pedido) => sum + (pedido.cantidad * pedido.precio), 0), ''
        ]], { origin: totalRow });
        worksheet[`E${totalRow + 1}`].s = { font: { bold: true } };
        worksheet[`G${totalRow + 1}`].s = { 
            font: { bold: true, color: { rgb: "FF0000" } },
            numFmt: '"S/"#,##0.00'
        };
    }
    if (tipo === 'pedidos') {
        ['F', 'G'].forEach(col => {
            for (let i = 2; i <= datos.length; i++) {
                const cell = worksheet[`${col}${i}`];
                if (cell) {
                    cell.z = '"S/"#,##0.00';
                }
            }
        });
    } else {
        ['D'].forEach(col => {
            for (let i = 2; i <= datos.length; i++) {
                const cell = worksheet[`${col}${i}`];
                if (cell) {
                    cell.z = '"S/"#,##0.00';
                }
            }
        });
    }
    
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Reporte');
    const fecha = new Date().toISOString().split('T')[0];
    XLSX.writeFile(workbook, `${nombreArchivo}_${fecha}.xlsx`);
    
    mostrarToast('Reporte Excel generado correctamente', 'success');
}
function formatearFecha(fechaString) {
    const fecha = new Date(fechaString);
    return fecha.toLocaleDateString('es-ES');
}

function mostrarToast(mensaje, tipo = 'info') {
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            transition: all 0.3s;
            opacity: 0;
            transform: translateX(100px);
        `;
        document.body.appendChild(toast);
    }
    
    const colores = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    
    toast.style.background = colores[tipo] || colores.info;
    toast.textContent = mensaje;
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(0)';
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100px)';
    }, 3000);
}