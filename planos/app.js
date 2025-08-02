function generarId() {
    const timestamp = Date.now().toString(36);
    const random = Math.random().toString(36).substr(2, 5);
    return `PLN-${timestamp}-${random}`.toUpperCase();
}

function formatearFecha(fecha) {
    return new Date(fecha).toLocaleString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function validarFormulario(form) {
    const campos = form.querySelectorAll('[required]');
    let valido = true;
    
    campos.forEach(campo => {
        if (!campo.value.trim()) {
            campo.style.borderColor = '#e74c3c';
            valido = false;
        } else {
            campo.style.borderColor = '#ddd';
        }
    });
    
    return valido;
}

function mostrarNotificacion(mensaje, tipo = 'success') {
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion ${tipo}`;
    notificacion.innerHTML = `
        <i class="fas ${tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i>
        <span>${mensaje}</span>
    `;
    
    document.body.appendChild(notificacion);
    
    setTimeout(() => {
        notificacion.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notificacion.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notificacion);
        }, 300);
    }, 3000);
}

function exportarTrabajos() {
    const trabajos = JSON.parse(localStorage.getItem('trabajos') || '[]');
    
    if (trabajos.length === 0) {
        mostrarNotificacion('No hay trabajos para exportar', 'error');
        return;
    }
    
    const dataStr = JSON.stringify(trabajos, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = `trabajos_planos_${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    
    mostrarNotificacion('Datos exportados correctamente');
}

function importarTrabajos(archivo) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
        try {
            const trabajosImportados = JSON.parse(e.target.result);
            
            if (!Array.isArray(trabajosImportados)) {
                throw new Error('Formato de archivo inválido');
            }
            
            const trabajosExistentes = JSON.parse(localStorage.getItem('trabajos') || '[]');
            const trabajosCombinados = [...trabajosExistentes];
            
            let nuevos = 0;
            trabajosImportados.forEach(trabajo => {
                if (!trabajosCombinados.find(t => t.id === trabajo.id)) {
                    trabajosCombinados.push(trabajo);
                    nuevos++;
                }
            });
            
            localStorage.setItem('trabajos', JSON.stringify(trabajosCombinados));
            mostrarNotificacion(`${nuevos} trabajos importados correctamente`);
            
            if (window.location.pathname.includes('trabajos.html')) {
                location.reload();
            }
            
        } catch (error) {
            mostrarNotificacion('Error al importar el archivo', 'error');
        }
    };
    
    reader.readAsText(archivo);
}

function buscarTrabajoPorId(id) {
    const trabajos = JSON.parse(localStorage.getItem('trabajos') || '[]');
    return trabajos.find(trabajo => trabajo.id === id);
}

function actualizarTrabajo(id, datosActualizados) {
    const trabajos = JSON.parse(localStorage.getItem('trabajos') || '[]');
    const indice = trabajos.findIndex(trabajo => trabajo.id === id);
    
    if (indice !== -1) {
        trabajos[indice] = { ...trabajos[indice], ...datosActualizados };
        localStorage.setItem('trabajos', JSON.stringify(trabajos));
        return true;
    }
    
    return false;
}

function eliminarTrabajoPorId(id) {
    const trabajos = JSON.parse(localStorage.getItem('trabajos') || '[]');
    const trabajosFiltrados = trabajos.filter(trabajo => trabajo.id !== id);
    
    localStorage.setItem('trabajos', JSON.stringify(trabajosFiltrados));
    return trabajos.length !== trabajosFiltrados.length;
}

function obtenerEstadisticas() {
    const trabajos = JSON.parse(localStorage.getItem('trabajos') || '[]');
    const hoy = new Date().toDateString();
    const esteMes = new Date().getMonth();
    const esteAno = new Date().getFullYear();
    
    return {
        total: trabajos.length,
        hoy: trabajos.filter(t => new Date(t.fecha).toDateString() === hoy).length,
        esteMes: trabajos.filter(t => {
            const fecha = new Date(t.fecha);
            return fecha.getMonth() === esteMes && fecha.getFullYear() === esteAno;
        }).length,
        porSoftware: trabajos.reduce((acc, trabajo) => {
            acc[trabajo.software] = (acc[trabajo.software] || 0) + 1;
            return acc;
        }, {})
    };
}

function validarCodigoQR(texto) {
    try {
        const data = JSON.parse(texto);
        return data.id && data.nombre && data.software;
    } catch (error) {
        return false;
    }
}

function generarReporte() {
    const trabajos = JSON.parse(localStorage.getItem('trabajos') || '[]');
    
    if (trabajos.length === 0) {
        mostrarNotificacion('No hay trabajos para generar reporte', 'error');
        return;
    }
    
    const contenidoReporte = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Reporte de Trabajos - Gestión de Planos</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .trabajo { border: 1px solid #ddd; margin: 10px 0; padding: 15px; }
                .trabajo h3 { color: #333; margin-top: 0; }
                .detalle { margin: 5px 0; }
                .software { background: #f0f0f0; padding: 2px 8px; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Reporte de Trabajos</h1>
                <p>Generado el: ${new Date().toLocaleString('es-ES')}</p>
                <p>Total de trabajos: ${trabajos.length}</p>
            </div>
            ${trabajos.map(trabajo => `
                <div class="trabajo">
                    <h3>${trabajo.nombre}</h3>
                    <div class="detalle"><strong>ID:</strong> ${trabajo.id}</div>
                    <div class="detalle"><strong>Software:</strong> <span class="software">${trabajo.software}</span></div>
                    <div class="detalle"><strong>Cliente:</strong> ${trabajo.cliente}</div>
                    <div class="detalle"><strong>Proyecto:</strong> ${trabajo.proyecto}</div>
                    <div class="detalle"><strong>Fecha:</strong> ${formatearFecha(trabajo.fecha)}</div>
                    ${trabajo.descripcion ? `<div class="detalle"><strong>Descripción:</strong> ${trabajo.descripcion}</div>` : ''}
                    ${trabajo.ubicacionArchivo ? `<div class="detalle"><strong>Ubicación:</strong> ${trabajo.ubicacionArchivo}</div>` : ''}
                </div>
            `).join('')}
        </body>
        </html>
    `;
    
    const ventana = window.open('', '_blank');
    ventana.document.write(contenidoReporte);
    ventana.document.close();
    ventana.print();
    
    mostrarNotificacion('Reporte generado correctamente');
}

document.addEventListener('DOMContentLoaded', function() {
    if (!document.querySelector('#notificacion-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notificacion-styles';
        styles.textContent = `
            .notificacion {
                position: fixed;
                top: 20px;
                right: 20px;
                background: #2ecc71;
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1000;
                display: flex;
                align-items: center;
                gap: 10px;
                transform: translateX(400px);
                transition: transform 0.3s ease;
            }
            
            .notificacion.error {
                background: #e74c3c;
            }
            
            .notificacion.show {
                transform: translateX(0);
            }
            
            .notificacion i {
                font-size: 1.2rem;
            }
        `;
        document.head.appendChild(styles);
    }
});

function nuevoTrabajo() {
    document.querySelector('.form-container').style.display = 'block';
    document.getElementById('qrResult').style.display = 'none';
    document.getElementById('trabajoForm').reset();
    document.getElementById('version').value = 'v1.0';
}

function mostrarDetallesTrabajo(id) {
    const trabajo = buscarTrabajoPorId(id);
    if (!trabajo) return;
    
    document.getElementById('modalTitulo').textContent = trabajo.nombre;
    document.getElementById('detalleId').textContent = trabajo.id;
    document.getElementById('detalleSoftware').textContent = trabajo.software;
    document.getElementById('detalleCliente').textContent = trabajo.cliente;
    document.getElementById('detalleProyecto').textContent = trabajo.proyecto;
    document.getElementById('detalleFecha').textContent = formatearFecha(trabajo.fecha);
    
    const descripcionItem = document.getElementById('detalleDescripcionItem');
    const ubicacionItem = document.getElementById('detalleUbicacionItem');
    const versionItem = document.getElementById('detalleVersionItem');
    
    if (trabajo.descripcion) {
        document.getElementById('detalleDescripcion').textContent = trabajo.descripcion;
        descripcionItem.style.display = 'block';
    } else {
        descripcionItem.style.display = 'none';
    }
    
    if (trabajo.ubicacionArchivo) {
        document.getElementById('detalleUbicacion').textContent = trabajo.ubicacionArchivo;
        ubicacionItem.style.display = 'block';
    } else {
        ubicacionItem.style.display = 'none';
    }
    
    if (trabajo.version) {
        document.getElementById('detalleVersion').textContent = trabajo.version;
        versionItem.style.display = 'block';
    } else {
        versionItem.style.display = 'none';
    }
    
    window.trabajoSeleccionado = trabajo;
    
    document.getElementById('modalDetalles').style.display = 'block';
}

function cerrarModal() {
    document.getElementById('modalDetalles').style.display = 'none';
}

function editarTrabajo() {
    if (!window.trabajoSeleccionado) return;
    
    mostrarNotificacion('Función de edición en desarrollo');
}

function eliminarTrabajo() {
    if (!window.trabajoSeleccionado) return;
    
    if (confirm('¿Estás seguro de que quieres eliminar este trabajo?')) {
        if (eliminarTrabajoPorId(window.trabajoSeleccionado.id)) {
            mostrarNotificacion('Trabajo eliminado correctamente');
            cerrarModal();
            if (typeof cargarTrabajos === 'function') {
                cargarTrabajos();
            }
        } else {
            mostrarNotificacion('Error al eliminar el trabajo', 'error');
        }
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('modalDetalles');
    if (event.target === modal) {
        cerrarModal();
    }
}