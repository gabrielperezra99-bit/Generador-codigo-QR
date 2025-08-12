// Visor DWG simplificado con múltiples opciones
class DWGViewerAdvanced {
    constructor(containerId) {
        this.containerId = containerId;
        this.currentPlano = null;
    }
    
    async displayDWG(plano) {
        this.currentPlano = plano;
        const container = document.getElementById(this.containerId);
        
        if (!container) {
            console.error('Contenedor no encontrado:', this.containerId);
            return;
        }
        
        // Para desarrollo local, usar visor optimizado
        this.displayLocalViewer(plano, container);
    }
    
    displayLocalViewer(plano, container) {
        container.innerHTML = `
            <div style="text-align: center; margin-bottom: 15px;">
                <h4 style="color: var(--primary); margin-bottom: 5px;">
                    <i class="fas fa-drafting-compass"></i> Vista del plano DWG
                </h4>
                <small style="color: var(--text-secondary);">Archivo: ${plano.archivo_nombre}</small>
            </div>
            
            <!-- Pestañas para diferentes opciones -->
            <div style="margin-bottom: 15px; text-align: center;">
                <button onclick="showPreview()" class="viewer-tab active" id="tab-preview">
                    <i class="fas fa-image"></i> Vista previa
                </button>
                <button onclick="showFileInfo()" class="viewer-tab" id="tab-info">
                    <i class="fas fa-info-circle"></i> Información
                </button>
                <button onclick="showDownloadOptions()" class="viewer-tab" id="tab-download">
                    <i class="fas fa-download"></i> Descargar
                </button>
            </div>
            
            <!-- Vista previa -->
            <div id="preview-section" class="viewer-section active">
                ${this.generatePreviewContent(plano)}
            </div>
            
            <!-- Información del archivo -->
            <div id="info-section" class="viewer-section" style="display: none;">
                ${this.generateFileInfo(plano)}
            </div>
            
            <!-- Opciones de descarga -->
            <div id="download-section" class="viewer-section" style="display: none;">
                ${this.generateDownloadOptions(plano)}
            </div>
            
            <!-- Mensaje para desarrollo local -->
            <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
                <div style="display: flex; align-items: center; margin-bottom: 8px;">
                    <i class="fas fa-info-circle" style="color: #2196f3; margin-right: 8px;"></i>
                    <strong style="color: #1976d2;">Modo desarrollo local</strong>
                </div>
                <p style="margin: 0; font-size: 0.9rem; color: #1565c0;">
                    Los visores web externos no funcionan en localhost. Para visualización completa, 
                    despliega en un servidor web o usa AutoCAD/software especializado.
                </p>
            </div>
            
            <style>
                .viewer-tab {
                    padding: 10px 20px;
                    margin: 0 5px;
                    border: 1px solid var(--border);
                    background: var(--bg-secondary);
                    color: var(--text-secondary);
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    transition: all 0.3s ease;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                }
                .viewer-tab:hover {
                    background: var(--surface-hover);
                    transform: translateY(-1px);
                }
                .viewer-tab.active {
                    background: var(--primary);
                    color: white;
                    border-color: var(--primary);
                    box-shadow: 0 2px 8px rgba(30, 64, 175, 0.3);
                }
                .viewer-section {
                    transition: opacity 0.3s ease;
                }
                .file-property {
                    display: flex;
                    justify-content: space-between;
                    padding: 12px 0;
                    border-bottom: 1px solid var(--border-light);
                }
                .file-property:last-child {
                    border-bottom: none;
                }
                .property-label {
                    font-weight: 600;
                    color: var(--text-secondary);
                }
                .property-value {
                    color: var(--text-primary);
                    font-weight: 500;
                }
            </style>
        `;
        
        this.setupLocalFunctions();
    }
    
    generatePreviewContent(plano) {
        if (plano.preview_url) {
            return `
                <div class="preview-container" style="text-align: center; padding: 20px; background: var(--bg-secondary); border-radius: 8px;">
                    <img src="${plano.preview_url}" alt="${plano.archivo_nombre}" 
                         style="max-width: 100%; max-height: 600px; object-fit: contain; border-radius: 8px; box-shadow: var(--shadow); cursor: pointer;"
                         onclick="openImageFullscreen('${plano.preview_url}')">
                    <div style="margin-top: 15px; font-size: 0.9rem; color: var(--text-secondary);">
                        <i class="fas fa-search-plus"></i> Haz clic en la imagen para verla en pantalla completa
                    </div>
                </div>
            `;
        } else {
            return `
                <div class="no-preview" style="text-align: center; padding: 60px 20px; background: var(--bg-tertiary); border-radius: 8px; border: 2px dashed var(--border);">
                    <i class="fas fa-file-alt" style="font-size: 4rem; margin-bottom: 20px; color: var(--accent);"></i>
                    <h4 style="margin-bottom: 15px; color: var(--text-primary);">Vista previa no disponible</h4>
                    <p style="margin-bottom: 10px; color: var(--text-secondary);">Este archivo DWG no tiene una imagen de previsualización generada.</p>
                    <div style="background: var(--surface); padding: 20px; border-radius: 6px; margin: 20px 0; text-align: left;">
                        <h5 style="margin: 0 0 10px 0; color: var(--primary);">💡 Para generar una vista previa:</h5>
                        <ul style="margin: 0; padding-left: 20px; color: var(--text-secondary);">
                            <li>Abre el archivo en AutoCAD</li>
                            <li>Exporta como imagen (JPG/PNG)</li>
                            <li>Sube la imagen como preview</li>
                        </ul>
                    </div>
                </div>
            `;
        }
    }
    
    generateFileInfo(plano) {
        const fileSize = plano.archivo_tamaño ? (plano.archivo_tamaño / 1024 / 1024).toFixed(2) + ' MB' : 'No disponible';
        const uploadDate = plano.fecha_creacion ? new Date(plano.fecha_creacion).toLocaleDateString('es-ES') : 'No disponible';
        
        return `
            <div style="background: var(--surface); padding: 24px; border-radius: 8px; border: 1px solid var(--border);">
                <h4 style="margin: 0 0 20px 0; color: var(--primary); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-info-circle"></i> Información del archivo
                </h4>
                
                <div class="file-property">
                    <span class="property-label">📁 Nombre del archivo:</span>
                    <span class="property-value">${plano.archivo_nombre}</span>
                </div>
                
                <div class="file-property">
                    <span class="property-label">📏 Formato:</span>
                    <span class="property-value">DWG (AutoCAD Drawing)</span>
                </div>
                
                <div class="file-property">
                    <span class="property-label">💾 Tamaño:</span>
                    <span class="property-value">${fileSize}</span>
                </div>
                
                <div class="file-property">
                    <span class="property-label">📅 Fecha de subida:</span>
                    <span class="property-value">${uploadDate}</span>
                </div>
                
                <div class="file-property">
                    <span class="property-label">👤 Cliente:</span>
                    <span class="property-value">${plano.cliente || 'No especificado'}</span>
                </div>
                
                <div class="file-property">
                    <span class="property-label">🔗 URL del archivo:</span>
                    <span class="property-value" style="word-break: break-all; font-family: monospace; font-size: 0.8rem;">${plano.archivo_url}</span>
                </div>
                
                ${plano.descripcion ? `
                    <div style="margin-top: 20px; padding: 15px; background: var(--bg-secondary); border-radius: 6px;">
                        <h5 style="margin: 0 0 8px 0; color: var(--primary);">📝 Descripción:</h5>
                        <p style="margin: 0; color: var(--text-primary);">${plano.descripcion}</p>
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    generateDownloadOptions(plano) {
        return `
            <div style="background: var(--surface); padding: 24px; border-radius: 8px; border: 1px solid var(--border);">
                <h4 style="margin: 0 0 20px 0; color: var(--primary); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-download"></i> Opciones de descarga
                </h4>
                
                <div style="display: grid; gap: 15px;">
                    <a href="${plano.archivo_url}" target="_blank" download
                       style="display: flex; align-items: center; gap: 12px; padding: 15px; background: var(--primary); color: white; text-decoration: none; border-radius: 8px; transition: all 0.3s ease;"
                       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(30, 64, 175, 0.3)'"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <i class="fas fa-file-download" style="font-size: 1.2rem;"></i>
                        <div>
                            <div style="font-weight: 600;">Descargar archivo original</div>
                            <div style="font-size: 0.9rem; opacity: 0.9;">Archivo DWG completo</div>
                        </div>
                    </a>
                    
                    ${plano.preview_url ? `
                        <a href="${plano.preview_url}" target="_blank" download
                           style="display: flex; align-items: center; gap: 12px; padding: 15px; background: var(--secondary); color: white; text-decoration: none; border-radius: 8px; transition: all 0.3s ease;"
                           onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(100, 116, 139, 0.3)'"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <i class="fas fa-image" style="font-size: 1.2rem;"></i>
                            <div>
                                <div style="font-weight: 600;">Descargar vista previa</div>
                                <div style="font-size: 0.9rem; opacity: 0.9;">Imagen de previsualización</div>
                            </div>
                        </a>
                    ` : ''}
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: var(--bg-secondary); border-radius: 6px;">
                    <h5 style="margin: 0 0 10px 0; color: var(--primary);">🛠️ Software recomendado para abrir DWG:</h5>
                    <ul style="margin: 0; padding-left: 20px; color: var(--text-secondary);">
                        <li><strong>AutoCAD</strong> - Software oficial de Autodesk</li>
                        <li><strong>DWG TrueView</strong> - Visor gratuito de Autodesk</li>
                        <li><strong>LibreCAD</strong> - Alternativa gratuita y open source</li>
                        <li><strong>FreeCAD</strong> - Software CAD gratuito</li>
                    </ul>
                </div>
            </div>
        `;
    }
    
    setupLocalFunctions() {
        window.showPreview = () => {
            this.switchSection('preview');
        };
        
        window.showFileInfo = () => {
            this.switchSection('info');
        };
        
        window.showDownloadOptions = () => {
            this.switchSection('download');
        };
        
        window.openImageFullscreen = (src) => {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                background: rgba(0,0,0,0.95); z-index: 10000; display: flex; 
                align-items: center; justify-content: center; cursor: pointer;
            `;
            modal.innerHTML = `
                <img src="${src}" style="max-width: 95%; max-height: 95%; object-fit: contain; border-radius: 8px;">
                <div style="position: absolute; top: 20px; right: 30px; color: white; font-size: 2.5rem; cursor: pointer; background: rgba(0,0,0,0.5); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">×</div>
                <div style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); color: white; background: rgba(0,0,0,0.7); padding: 10px 20px; border-radius: 20px; font-size: 0.9rem;">Haz clic en cualquier lugar para cerrar</div>
            `;
            modal.onclick = () => document.body.removeChild(modal);
            document.body.appendChild(modal);
        };
    }
    
    switchSection(section) {
        // Ocultar todas las secciones
        document.querySelectorAll('.viewer-section').forEach(s => s.style.display = 'none');
        document.querySelectorAll('.viewer-tab').forEach(t => t.classList.remove('active'));
        
        // Mostrar la sección seleccionada
        document.getElementById(section + '-section').style.display = 'block';
        document.getElementById('tab-' + section).classList.add('active');
    }
}

// Instancia global
window.dwgViewer = new DWGViewerAdvanced('archivo-viewer-content');
